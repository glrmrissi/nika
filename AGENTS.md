# nika — Symfony 8.1 + PHP 8.4+ + SQLite + SRS (SM-2)

kana.pro clone.

## Setup

```
php bin/console app:kanji:seed          # populate JLPT N5-N1 kanji from src/Data/KanjiData.php
php bin/console app:admin:create        # uses ADMIN_EMAIL/ADMIN_PASSWORD from .env
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php -S localhost:8000 -t public         # dev server
```

## Routes

All `#[Route]` attribute except `/2fa` and `/2fa_check` which are YAML-defined in `config/routes/scheb_2fa.yaml`.

| Method | Path | Name |
|--------|------|------|
| GET | `/` | `app_home` |
| GET/POST | `/login` | `app_login` (form_login, CSRF off on this path only) |
| GET | `/logout` | `app_logout` |
| GET/POST | `/register` | `app_register` |
| GET | `/verify-email` | `app_verify_email` (query: `token`) |
| GET/POST | `/forgot-password` | `app_forgot_password` |
| GET/POST | `/reset-password/{token}` | `app_reset_password` (path param) |
| GET/POST | `/2fa` | `2fa_login` (scheb, YAML route) |
| POST | `/2fa_check` | `2fa_login_check` (scheb, YAML route) |
| GET/POST | `/2fa/setup` | `app_2fa_setup` |
| GET | `/2fa/disable` | `app_2fa_disable` |
| GET | `/profile` | `app_profile` |
| GET/POST | `/profile/edit` | `app_profile_edit` |
| POST | `/profile/avatar/remove` | `app_profile_avatar_remove` |
| GET | `/auth/me` | `app_auth_me` (JSON) |
| GET | `/review` | `app_review` |
| GET | `/review/next` | `app_review_next` (JSON, query: `level`) |
| POST | `/review/submit` | `app_review_submit` (JSON, body: `kanji_id`, `quality`) |
| GET | `/kanji/{id}` | `app_kanji_detail` (JSON) |
| GET | `/kanji` | `app_kanji_list` (HTML) |
| GET | `/api/kanji` | `app_api_kanji` (JSON, query: `page`, `level`, `status`) |
| POST | `/kanji/{id}/select` | `app_kanji_select` (JSON toggle selection) |
| POST | `/api/kanji/select-batch` | `app_kanji_select_batch` (JSON, body: `action`, `ids`/`level`) |
| POST | `/kanji/{id}/toggle-done` | `app_kanji_toggle_done` (JSON toggle isComplete) |

## Conventions

- **No comments.** English names everywhere.
- **PHP 8.4+**: typed properties, constructor promotion, Doctrine attributes, named-argument constraints (`new NotBlank(message: '...')`), no array-style constraints.
- **Login required.** `PUBLIC_ACCESS` on `/login`, `/register`, `/forgot-password`, `/reset-password`, `/verify-email`, `/2fa`, `/kanji`, `/api/kanji`. POST `/kanji/{id}/select` returns 401 JSON if unauthenticated (controller checks manually, access_control allows the path publicly).
- **CSRF**: `framework.csrf_protection` with stateless tokens (`submit`, `authenticate`, `logout`). Form types set `csrf_protection: false`; `security.yaml form_login` has `enable_csrf: false` (SameOriginCsrfTokenManager incompatible without Stimulus/UX). 2FA forms also disable CSRF.
- **Password rules**: server min 8 with uppercase+lowercase+digit. Hashed via `UserPasswordHasherInterface`.
- **Anti-bot**: login form has hidden honeypot fields (`website`, `confirm_email`). `HoneypotSubscriber` checks at priority 20 and 403s + Discord notify if filled.
- **2FA (TOTP)**: `scheb/2fa-bundle` + `scheb/2fa-totp`. `security_tokens` in `scheb_2fa.yaml` MUST include `UsernamePasswordToken` because `FormLoginAuthenticator::createToken()` returns `UsernamePasswordToken` — if missing, `AuthenticatedTokenCondition` silently skips 2FA (no error, no redirect). Also include `PostAuthenticationToken` and `RememberMeToken`. `auth_form_path` and `check_path` in `security.yaml` two_factor config are literal paths (`/2fa`, `/2fa_check`), not route names — `httpUtils->createRedirectResponse()` expects paths. Secret stored encrypted via `TotpEncryptionListener` (postLoad/prePersist/preUpdate). QR codes via `endroid/qr-code-bundle`. `TotpEncryptionService` uses `aes-256-gcm` with key derived via `hash('sha256', APP_SECRET . ':totp-pepper-v1', true)`. `OTPHP\TOTP` is a transitive dependency (from `scheb/2fa-totp`) used directly in the setup controller.
- **Login success**: `LoginSuccessHandler` sets `_login_success` session flag, used by dashboard to show login overlay.
- **Avatar uploads**: `public/uploads/avatars/`, max 2 MB, JPEG/PNG/WebP only. Old file removed on replace.

## Database

- `var/data.db` (SQLite). **No naming strategy** — entities use explicit `#[ORM\Table(name: "lowercase_name")]` (SQLite on Windows is case-insensitive for table names).
- Tables: `user`, `kanji`, `review_log`, `user_kanji`, `name_history`.

## SM-2 (SrsService)

- quality: 0-5, EF starts at 2.5 (min 1.3)
- quality < 3: reset (repetitions=0, interval=1)
- quality >= 3: intervals are 1, 6, then `round(interval * EF)`; repetitions++
- SRS state stored per-user in `UserKanji` entity (not on `Kanji`). `SrsService.review()` finds or creates `UserKanji` for the current user.
- Auto-mastery: `isComplete` set when `reps >= 3 && interval >= 30 && EF >= 2.5`.

## Timezone

- `User.timezone` nullable, `getEffectiveTimezone()` falls back to `date_default_timezone_get()`.
- `UserRepository`, `KanjiRepository`, `ReviewLogRepository` methods accept `$timezone` param (default `'UTC'`). Dashboard and profile controllers pass `$user->getEffectiveTimezone()`.
- `ProfileFormType` and `RegistrationFormType` include timezone select.

## CSS

- Pure modular CSS in `public/css/`:
  - `base.css` — design tokens (border-radius: 0 on all)
  - `dark.css` — `[data-theme="dark"]` overrides (primary: `#FF453A`)
  - `layout.css`, `components/{topbar,mobile-nav,ui}.css`, `pages/{auth,dashboard,profile,review}.css`
- Font: Inter (body via Google), Times New Roman serif (headings). Icons: Bootstrap Icons CDN.
- Dark theme default (`localStorage.getItem('theme') || 'dark'`), toggle in topbar.
- **No gratuitous padding/margin.** No background highlights on hover — only `border-bottom` or color transitions. No border-radius unless essential.
- **Topbar nav links**: `border-bottom: 2px solid transparent` — visible on hover (`--color-secondary`) and active (`--color-primary`). No background, no border-radius, padding only `8px 4px`.
- **Avatar dropdown**: opens on hover (desktop) and click (JS toggles `.topbar__dropdown--open`). Closes on outside click.
- **Modal**: data-attribute driven (`data-modal`). Defined in `base.js`. Buttons show `(Y)` / `(N)` keyboard hints; Y/y confirms, N/n or Escape cancels.
- All hardcoded `rgba(r,g,b,x)` replaced with `color-mix(in srgb, var(--color-primary) X%, transparent)` for theme adaptability.

- **Profile is public**. `/profile` shows public info (name, avatar, stats, badges, name history). Own profile shows edit button, email, 2FA section, and camera overlay via `isOwnProfile` variable passed from controller.

## Architecture

- `src/Entity/{Kanji,NameHistory,ReviewLog,User,UserKanji}.php`
- `src/Repository/{Kanji,NameHistory,ReviewLog,User}Repository.php`
- `src/Service/SrsService.php` (SM-2), `DiscordNotifier.php`, `TotpEncryptionService.php`
- `src/Controller/{Dashboard,Review,Kanji,Profile,Registration,ResetPassword,Security,TwoFactor}Controller.php`
- `src/Form/{RegistrationForm,ProfileForm,ForgotPassword,ResetPassword,TwoFactorSetup}FormType.php` — `TwoFactorVerifyFormType` is unused dead code
- `src/Security/LoginSuccessHandler.php`
- `src/EventSubscriber/HoneypotSubscriber.php`
- `src/Doctrine/TotpEncryptionListener.php`
- `src/Command/{SeedKanjiCommand,CreateAdminCommand}.php`
- `src/Data/KanjiData.php` — seeded kanji data (581 entries)
- No tests directory exists.

## Git

- **Conventional commits**: `feat:`, `fix:`, `refactor:`, `chore:`, `docs:`. One line only, no body. If already committed without prefixes, do `git reset --soft HEAD~N` and recommit one by one.
- **Always check `git status` + `git diff` before committing.** Never commit sensitive data (secrets, `.env`, `out.json`).
- **Avoid rebasing in-progress work** — stuck rebase must be aborted with `git rebase --abort`, then recover lost commits from `git reflog` + `git reset --hard <hash>`.

## Skills

- `.agents/skills/bic-design/` — Bic blue borderless design system (Hermes style)
- `.agents/skills/design-system/` — token architecture, component specs
- `.agents/skills/interface-design/` — craft-first product UI design
- `.agents/skills/revenue-centric-design/` — conversion, retention, monetization, pricing, onboarding, landing pages
- `.agents/skills/web-design-guidelines/` — UI/UX best practices review
