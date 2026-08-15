# FSRS Review System

## Assessment

The review migration is functional and the persistence model is in place. It should be treated as an FSRS-based scheduler with an application adapter, not as an untouched drop-in implementation of every option exposed by the Composer package.

The application uses `scottlaurent/fsrs` for the core difficulty/stability calculations. `SrsService` owns the product-specific short-term learning steps and deterministic long-interval fuzzing so that the interval shown in the UI is the interval saved to the database.

## Ratings

Every rating endpoint uses the same four-value contract:

| Rating | Meaning | Kanji standard review | Interactive review | Grammar quiz |
|---:|---|---|---|---|
| 1 | Again | Forgot | Wrong answer | Wrong answer |
| 2 | Hard | Difficult recall | Not used automatically | Not used automatically |
| 3 | Good | Normal recall | Correct answer | Correct answer |
| 4 | Easy | Immediate recall | Not used automatically | Not used automatically |

The old `quality` field remains in `review_log` for compatibility and receives the same numeric value as `rating` for kanji reviews. New JSON requests must send `rating`, not `quality`.

## Card states

The state is stored per user and per item in `user_kanji` or `user_particle`:

| Value | State | Meaning |
|---:|---|---|
| 0 | New | Never reviewed by this user |
| 1 | Learning | Short-term learning steps |
| 2 | Review | Long-term FSRS scheduling |
| 3 | Relearning | Short-term recovery after forgetting |

Product learning configuration:

- Learning steps: `1m`, `10m`
- Relearning steps: `10m`
- Request retention: `0.90`
- Maximum long-term interval: `36500` days
- Long-term fuzzing: deterministic, applied to intervals of at least 3 days

Short-term transitions are handled by `SrsService`:

- New + Again → Learning step 0, `1m`
- New + Hard → Learning step 0, `6m` midpoint of the learning steps
- New + Good → Learning step 1, `10m`
- New + Easy → Review immediately with a long-term interval
- Learning + Again → Learning step 0, `1m`
- Learning + Good at the final step → Review
- Review + Again → Relearning step 0, `10m`
- Relearning + Good at the final step → Review

The Composer package exposes learning-step and fuzzing options, but version `v0.1` does not consume all of those options in its internal scheduler. The application adapter therefore applies them after generating the core FSRS outcomes. Do not remove that adapter without adding equivalent behavior and canonical scheduler tests.

## Persistence model

`user_kanji` and `user_particle` store:

- `stability`: memory stability in days
- `difficulty`: FSRS difficulty value
- `state`: state value from the table above
- `lapses`: number of lapses recorded by the scheduler
- `step`: current learning/relearning step
- `lastReviewedAt`: last review instant in UTC
- `nextReviewAt`: next due instant in UTC
- `repetitions`: retained legacy counter used by the FSRS card adapter
- `easeFactor` and `interval`: legacy SM-2 columns kept for schema compatibility; they are no longer updated by the scheduler

`review_log` stores FSRS snapshots for kanji reviews:

- `rating`
- `cardState`
- `stability`
- `difficulty`
- `scheduledDays`
- `elapsedDays`

Grammar review and quiz currently persist their scheduling state and activity metadata, but do not create `review_log` rows because that table is still kanji-specific. A future unified review-history migration should add a nullable grammar subject or introduce a separate `particle_review_log` table.

## Review flow

1. A selected kanji is returned by `GET /review/next` only when its `nextReviewAt` is due.
2. The endpoint returns the card, state label, and four computed interval previews.
3. The browser shows the answer and sends `POST /review/submit` with `kanji_id` and `rating`.
4. `SrsService` rebuilds the FSRS card in UTC, generates all outcomes, applies the selected outcome, updates the per-user state, creates a kanji snapshot, creates an activity, and flushes the unit of work.
5. A kanji that is no longer due returns HTTP 409. This prevents sequential stale/double submissions from incrementing the card twice.
6. Grammar review and quiz allow early practice by design. Their frontend disables the answer controls while a request is pending.

Doctrine wraps `flush()` in a database transaction. The state update, kanji snapshot, and activity are therefore committed together for a successful kanji review.

## API contract

### `GET /review/next`

Returns either:

```json
{"done": true}
```

or:

```json
{
  "done": false,
  "stage": "Learning",
  "intervals": {"1": "1m", "2": "6m", "3": "10m", "4": "14d"},
  "kanji": {
    "id": 1,
    "character": "日",
    "meanings": "day, sun",
    "onyomi": "ニチ, ジツ",
    "kunyomi": "ひ, か",
    "jlptLevel": "N5",
    "strokeCount": 4
  }
}
```

### `POST /review/submit`

```json
{"kanji_id": 1, "rating": 3}
```

Responses include `success: true`, or an error status for invalid JSON, invalid input, CSRF failure, rate limiting, a missing selected kanji, or a card that is no longer due.

Grammar review uses the same rating names at `/api/grammar/review/submit`. The quiz computes Good for a correct answer and Again for an incorrect answer at `/api/grammar/quiz/submit`.

## Migrations

`Version20260731214446`:

- Adds FSRS columns to `user_kanji` and `user_particle`.
- Adds kanji review snapshot columns to `review_log`.
- Seeds stability, difficulty, state, and last-review timestamps from legacy SM-2 values.

`Version20260731221102`:

- Corrects migrated cards with one or two SM-2 repetitions to Learning instead of Review.
- Recovers kanji lapse counts from legacy `review_log.quality` values below 3.
- Backfills a missing `lastReviewedAt` when possible.

The SM-2-to-FSRS conversion is necessarily approximate because the old schema does not contain the FSRS stability, difficulty, state, or complete review history required to replay the model exactly. Existing cards should be allowed to recalibrate through normal reviews.

Run migrations with:

```bash
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
```

## Verification

Useful checks after changing the scheduler:

```bash
php -l src/Service/SrsService.php
php bin/console cache:clear
php bin/console doctrine:schema:validate
php bin/console doctrine:migrations:status
php bin/console debug:router
```

A scheduler smoke check should cover at least:

- New → Good → Learning step 1
- Learning → Good → Review
- Review → Again → Relearning
- Relearning → Good → Review
- Hard/Easy interval ordering
- UTC persistence
- duplicate kanji submission returning 409
- preview interval matching the saved due interval

## Known limitations and backlog

1. Grammar reviews do not yet have normalized review snapshots. Activity metadata is not a replacement for a queryable review-history table.
2. The interactive kanji mode maps every correct answer to Good and every wrong answer to Again. It cannot distinguish a fast Easy answer from a hesitant Good answer.
3. Again cards become due after their learning step; the current endpoint does not inject future learning cards into the same HTTP request/session.
4. Legacy SM-2 columns remain in the schema and should be removed only in a separately planned cleanup migration after downstream readers are confirmed.
5. The migration does not replay every historical review through FSRS. It seeds approximate values and intentionally avoids destructive history changes.
6. The app uses deterministic fuzzing so interval previews and persisted due dates agree. If true per-review randomness is introduced later, the selected interval must be returned by the submit endpoint and shown from the saved result rather than recomputed in the browser.
7. The `scottlaurent/fsrs` dependency is small and pinned to `v0.1`. Keep canonical FSRS fixtures in the project before changing or upgrading the scheduler dependency.
8. `doctrine:schema:validate` still reports the repository's pre-existing SQLite index/foreign-key naming drift. The generated diff contains no missing FSRS columns; do not apply that diagnostic diff as an application migration.
