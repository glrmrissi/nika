<p align="center">
  <img src="public/assets/logo/ms-icon-310x310.png" alt="nika logo" width="180">
</p>

<p align="center">Japanese kanji and grammar review with FSRS spaced repetition.</p>

<p align="center">
  <a href="https://github.com/glrmrissi/nika/releases/latest"><img src="https://img.shields.io/github/v/release/glrmrissi/nika?style=flat-square&color=1DB954&label=version" alt="Release"></a>
  <a href="https://github.com/glrmrissi/nika/stargazers"><img src="https://img.shields.io/github/stars/glrmrissi/nika?style=flat-square&color=1DB954&label=stars" alt="Stars"></a>
  <a href="https://github.com/glrmrissi/nika/actions/workflows/ci.yml"><img src="https://img.shields.io/github/actions/workflow/status/glrmrissi/nika/ci.yml?style=flat-square&label=build" alt="Build"></a>
  <a href="https://github.com/glrmrissi/nika/blob/main/LICENSE"><img src="https://img.shields.io/github/license/glrmrissi/nika?style=flat-square&label=license" alt="License"></a>
  <a href="https://www.php.net/"><img src="https://img.shields.io/badge/PHP-8.4%2B-777BB4?style=flat-square&logo=php&logoColor=white" alt="PHP 8.4+"></a>
  <a href="https://symfony.com/"><img src="https://img.shields.io/badge/Symfony-8.1-000000?style=flat-square&logo=symfony&logoColor=white" alt="Symfony 8.1"></a>
  <a href="https://www.postgresql.org/"><img src="https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql&logoColor=white" alt="PostgreSQL"></a>
  <a href="https://www.sqlite.org/"><img src="https://img.shields.io/badge/dev-SQLite-003B57?style=flat-square&logo=sqlite&logoColor=white" alt="SQLite (dev)"></a>
  <a href="#setup"><img src="https://img.shields.io/badge/platform-linux%20%7C%20windows-blue?style=flat-square" alt="Linux | Windows"></a>
</p>

nika is a self-hosted Japanese learning app built with Symfony 8.1 and PHP 8.4. It covers 1091 kanji across JLPT levels N5 to N1 and 16 grammar particles, using the FSRS spaced repetition algorithm (the same one Anki uses since 23.10) to schedule reviews. The interface is a kana.pro-inspired quiz flow with immediate feedback, per-character progress tracking, and a dark-first design.

> FSRS (Free Spaced Repetition Scheduler) models memory using stability, difficulty, and retrievability. nika wraps `scottlaurent/fsrs` with application-owned learning steps, deterministic interval fuzzing, and auto-mastery at stability >= 21 days.

<table>
  <tr>
    <td align="center"><img src="public/assets/showcases/b-1.jpg" alt="Dark theme review" width="400"></td>
    <td align="center"><img src="public/assets/showcases/b-2.jpg" alt="Dark theme kanji grid" width="400"></td>
  </tr>
  <tr>
    <td align="center"><img src="public/assets/showcases/w-1.jpg" alt="Light theme review" width="400"></td>
    <td align="center"><img src="public/assets/showcases/w-2.jpg" alt="Light theme kanji grid" width="400"></td>
  </tr>
</table>

## Features

- **1091 kanji** from JLPT N5 through N1, each with onyomi, kunyomi, meanings, and stroke count
- **16 grammar particles** with usage notes, examples in Japanese and romaji, and quiz mode
- **FSRS scheduling** with Again, Hard, Good, Easy ratings, learning steps, relearning steps, and interval previews
- **Interactive review** mode with immediate correct or wrong feedback
- **Grammar quiz** with multiple choice and per-particle stats
- **Dashboard** with SVG activity heatmap and streak tracking
- **Public profiles** with Markdown readme support
- **TOTP two-factor authentication** with encrypted secret storage (AES-256-GCM)
- **Email verification** and password reset flow
- **Rate limiting** on login, registration, review submit, and batch operations
- **CSP headers** via NelmioSecurityBundle with nonce-based inline script protection
- **Dark theme** by default with light theme toggle
- **Modular CSS** with design tokens, no CSS framework, no jQuery, vanilla JS

## Setup

### Quick install

```bash
git clone https://github.com/glrmrissi/nika.git
cd nika
composer setup
```

`composer setup` copies `.env.example` to `.env`, installs dependencies, creates the SQLite database, runs migrations, seeds 1091 kanji, creates an admin user from `ADMIN_EMAIL` and `ADMIN_PASSWORD` env vars, and clears the cache. The default `DATABASE_URL` uses SQLite for local development. For production, set `DATABASE_URL` to a PostgreSQL connection string before running setup.

### Start the dev server

```bash
composer dev
```

This runs `php -S localhost:8000 -t public`. Open `http://localhost:8000` in your browser.

### Windows

```powershell
.\scripts\dev.ps1 -Setup
```

If execution policy blocks it:

```powershell
powershell -ExecutionPolicy Bypass -File .\scripts\dev.ps1 -Setup
```

### Manual setup

```bash
cp .env.example .env
composer install
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:kanji:seed
php bin/console app:admin:create
php bin/console cache:clear
```

After `.env` changes, run `composer dump-env dev` and restart the dev server.

### Production

For production deployment, use PostgreSQL instead of SQLite. Set the `DATABASE_URL` in your `.env.local` or environment variables:

```bash
DATABASE_URL="postgresql://user:password@127.0.0.1:5432/nika?server_version=16&charset=utf8"
```

Then run the setup commands against the production database:

```bash
composer install --no-dev --optimize-autoloader
php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:kanji:seed
php bin/console app:admin:create
php bin/console cache:clear --env=prod
```

## Usage

### Kanji review

Browse kanji at `/kanji`, filter by JLPT level, and select which ones to study. Due reviews appear at `/review` with four rating buttons (Again, Hard, Good, Easy). The `/review/interactive` mode shows one card at a time with immediate correct or wrong feedback instead of self-rated buttons.

### Grammar review

The `/grammar` page lists all particles with examples. `/grammar/review` runs SRS-scheduled particle review, and `/grammar/quiz` offers a multiple-choice practice mode that can be used ahead of schedule.

### API endpoints

All review and data endpoints return JSON. POST endpoints require a `X-CSRF-Token` header with a stateless token.

| Method | Path | Purpose |
|-------|------|---------|
| GET | `/api/kanji` | Paginated kanji list, filter by `level` and `status` |
| GET | `/api/kanji/recent` | Recently viewed kanji |
| POST | `/api/kanji/select-batch` | Bulk select kanji for study |
| GET | `/api/grammar` | Paginated grammar particle list |
| GET | `/api/grammar/{id}` | Single particle detail |
| GET | `/api/grammar/review/next` | Next due particle for review |
| POST | `/api/grammar/review/submit` | Submit a review rating |
| GET | `/api/grammar/quiz/next` | Next quiz question |
| POST | `/api/grammar/quiz/submit` | Submit a quiz answer |
| GET | `/api/grammar/quiz/stats` | Per-particle quiz statistics |

## Configuration

### Environment variables

Key variables in `.env`:

| Variable | Purpose |
|----------|---------|
| `ADMIN_EMAIL` | Admin user email for `app:admin:create` |
| `ADMIN_PASSWORD` | Admin user password |
| `APP_SECRET` | Symfony secret, also used as TOTP encryption pepper |
| `DATABASE_URL` | Database connection. SQLite for dev (`sqlite:///%kernel.project_dir%/var/data.db`), PostgreSQL for production (`postgresql://user:pass@host:5432/dbname?server_version=16`) |
| `MAILER_DSN` | Mail transport for email verification and password reset |
| `ASSET_VERSION` | Cache-busting version for `asset()` calls |

### Security

CSRF is stateless: form types set `csrf_protection` to false, POST endpoints read `X-CSRF-Token` header. Inline scripts use CSP nonces via `{{ csp_nonce('script') }}`. TOTP secrets are encrypted with AES-256-GCM using a key derived from `APP_SECRET` and a pepper constant. Rate limits are 5 per minute on login, 3 per hour on registration, 60 per minute on review submit.

### Frontend

CSS lives in `public/css/` with `base.css` for design tokens, `dark.css` for the dark theme, and `pages/*.css` for page-specific styles. JS is vanilla in `public/js/` with no jQuery or Stimulus. Font is Inter for body text, Times New Roman serif for headings. Icons are Bootstrap Icons via CDN.

## Developer workflow

| Task | Composer | Make |
|------|----------|------|
| Full setup | `composer setup` | `make setup` |
| Dev server | `composer dev` | `make dev` |
| Run migrations | `composer db:migrate` | `make db-migrate` |
| Seed kanji and grammar | `composer db:seed` | `make db-seed` |
| Reset database | `composer db:reset` | `make db-reset` |
| Lint PHP, Twig, container, YAML | `composer lint` | `make lint` |
| Full quality gate | `composer check` | `make check` |
| Install Git hooks | `composer setup:hooks` | `make githooks` |

The quality gate (`composer check`) runs:

1. PHP syntax lint for `src`, `migrations`, `public`, `tests`
2. Twig template lint
3. Symfony container lint
4. YAML config lint
5. Doctrine migrations up-to-date check
6. `composer validate --strict`
7. `composer audit --locked`

Enable the Git pre-commit hook so fast checks run before every commit:

```bash
composer setup:hooks
```

## Continuous integration

GitHub Actions runs `composer check` plus database creation and migrations on every push and pull request to `main` or `master`. See [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

## FSRS documentation

See [`docs/FSRS.md`](docs/FSRS.md) for the state model, API contract, migration notes, verification commands, and known limitations.

## License

MIT
