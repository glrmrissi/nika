# nika — Symfony 8.1 + PHP 8.4+ + SQLite + SM-2

kana.pro clone.

## Setup

```
php bin/console app:kanji:seed          # 1091 kanji from src/Data/KanjiData.php
php bin/console app:admin:create        # ADMIN_EMAIL/ADMIN_PASSWORD from .env
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php -S localhost:8000 -t public
```

After `.env` changes, run `composer dump-env dev` and restart dev server.

## Routes

All `#[Route]` attribute (no YAML routing) except `/2fa` and `/2fa_check` in `config/routes/scheb_2fa.yaml`.

| Method | Path | Name |
|--------|------|------|
| GET | `/` | `app_home` |
| GET/POST | `/login` | `app_login` |
| GET | `/logout` | `app_logout` |
| GET/POST | `/register` | `app_register` |
| GET | `/verify-email` | `app_verify_email` (query: `token`) |
| GET/POST | `/forgot-password` | `app_forgot_password` |
| GET/POST | `/reset-password/{token}` | `app_reset_password` |
| GET/POST | `/2fa` | `2fa_login` (YAML) |
| POST | `/2fa_check` | `2fa_login_check` (YAML) |
| GET/POST | `/2fa/setup` | `app_2fa_setup` |
| POST | `/2fa/disable` | `app_2fa_disable` |
| GET | `/profile` | `app_profile` (public, own shows edit/2FA via `isOwnProfile`) |
| GET/POST | `/profile/edit` | `app_profile_edit` |
| POST | `/profile/avatar/remove` | `app_profile_avatar_remove` |
| GET | `/auth/me` | `app_auth_me` (JSON) |
| GET | `/review` | `app_review` |
| GET | `/review/interactive` | `app_review_interactive` |
| GET | `/review/next` | `app_review_next` (JSON, query: `level`) |
| POST | `/review/submit` | `app_review_submit` (JSON) |
| GET | `/kanji` | `app_kanji_list` (HTML) |
| GET | `/kanji/{id}` | `app_kanji_detail` (JSON) |
| POST | `/kanji/{id}/select` | `app_kanji_select` (JSON toggle) |
| POST | `/kanji/{id}/toggle-done` | `app_kanji_toggle_done` (JSON) |
| GET | `/kanji/recent` | `app_kanji_recent` (HTML) |
| GET | `/api/kanji` | `app_api_kanji` (JSON, query: `page`, `level`, `status`) |
| GET | `/api/kanji/recent` | `app_api_kanji_recent` (JSON) |
| POST | `/api/kanji/select-batch` | `app_kanji_select_batch` (JSON) |

## Security & CSP

- **NelmoSecurityBundle v3.9**: `config/packages/nelmio_security.yaml`. `level1_fallback: false`. `script-src: ["'self'"]` (no `'unsafe-inline'`). All inline `<script>` use `nonce="{{ csp_nonce('script') }}"`. `style-src` includes `'unsafe-inline'` + Google Fonts + Bootstrap Icons CDN.
- **CSRF**: Stateless tokens (`submit`, `authenticate`, `logout`, `api`). Form types all set `csrf_protection: false`. `form_login` has `enable_csrf: false`. POST endpoints read `X-CSRF-Token` header.
- **Anti-bot**: Login form has hidden honeypot fields (`website`, `confirm_email`). `HoneypotSubscriber` checks at priority 20, 403s + Discord notify if filled.
- **2FA (TOTP)**: `scheb/2fa-bundle` + `scheb/2fa-totp`. `security_tokens` in `scheb_2fa.yaml` MUST include `UsernamePasswordToken` (FormLoginAuthenticator returns this). `auth_form_path` and `check_path` are literal paths, not route names. Secret stored encrypted via `TotpEncryptionListener` (aes-256-gcm, key = `sha256(APP_SECRET . ':totp-pepper-v1')`). QR codes via `endroid/qr-code-bundle`.
- **Rate limiting**: 5/min login, 5/min 2fa, 3/hr registration, 3/hr password reset, 60/min review submit, 10/min batch select. Defined in `framework.yaml`, enforced in `RateLimitSubscriber` (login/2fa) and controllers (review submit, batch select).
- **Password**: min 8, uppercase+lowercase+digit, hashed via `UserPasswordHasherInterface`. 2FA disable also requires password re-entry.

## Database

- `var/data.db` (SQLite). **No naming strategy** — entities use `#[ORM\Table(name: "lowercase_name")]`.
- Tables: `user`, `kanji`, `review_log`, `user_kanji`, `name_history`.

## SM-2 (SrsService)

- Quality 0-5, EF starts at 2.5 (min 1.3).
- quality < 3: reset (repetitions=0, interval=1).
- quality >= 3: intervals 1, 6, then `round(interval * EF)`; repetitions++.
- Auto-mastery (`isComplete`): reps >= 3 && interval >= 30 && EF >= 2.5.
- SRS state is per-user in `UserKanji` entity.

## Frontend

- Pure modular CSS in `public/css/`: `base.css` (tokens), `dark.css` (primary `#FF453A`), `layout.css`, `components/*.css`, `pages/*.css`.
- Font: Inter (body), Times New Roman serif (headings). Icons: Bootstrap Icons CDN.
- Dark theme default (`localStorage`). Theme init runs in `<head>` before any rendering.
- JS in `public/js/`: `base.js` (modal, theme toggle, mobile-nav), `components/tooltip.js`, `pages/*.js`.
- No jQuery, no Stimulus/UX — vanilla JS everywhere.
- **No `'unsafe-inline'` in `script-src`**. External scripts (`<script src>`) need no nonce.
- SVG heatmaps, not HTML tables.

## Twig

- `|markdown` filter (league/commonmark + html-sanitizer, allowlist tags only).
- `{{ app_streak() }}` function (session-cached, 5-min TTL).
- Asset versioning via `{{ asset('...') }}` with `ASSET_VERSION` env var.

## Conventions

- **No comments in code**. English names.
- **PHP 8.4+**: typed properties, constructor promotion, Doctrine attributes, named-argument constraints.
- **Conventional commits**: `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`. One line, no body.
- **Avatar uploads**: `public/uploads/avatars/`, max 2 MB, JPEG/PNG/WebP. Old file removed on replace.
- `style-src` uses `unsafe-inline` (intentional — inline `<style>` in Twig blocks). Keep as-is.
- No tests directory exists.

## Skills

- `.agents/skills/bic-design/` — borderless design (Hermes style)
- `.agents/skills/interface-design/` — craft-first product UI
- `.agents/skills/revenue-centric-design/` — conversion/retention/monetization
- `.agents/skills/web-design-guidelines/` — UI/UX review
