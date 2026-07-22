# KanjiReview — Symfony 8.1 + SQLite + SRS (SM-2)

kana.pro clone.

## Setup

```
php bin/console app:kanji:seed          # populate JLPT N5-N1 kanji from src/Data/KanjiData.php
php bin/console app:admin:create        # uses ADMIN_EMAIL/ADMIN_PASSWORD from .env
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php -S localhost:8000 -t public         # dev server
```

## Routes (all `#[Route]` attribute)

| Method | Path | Name |
|--------|------|------|
| GET | `/` | `app_home` |
| GET/POST | `/login` | `app_login` (form_login, CSRF off on this path only) |
| GET | `/logout` | `app_logout` |
| GET/POST | `/register` | `app_register` |
| GET | `/verify-email` | `app_verify_email` (query: `token`) |
| GET/POST | `/forgot-password` | `app_forgot_password` |
| GET/POST | `/reset-password` | `app_reset_password` (query: `token`) |
| GET/POST | `/2fa` | `2fa_login` (scheb) |
| POST | `/2fa_check` | `2fa_login_check` |
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
| GET | `/api/kanji` | `app_api_kanji` (JSON, query: `page`, `level`) |

## Conventions

- **No comments.** English names everywhere.
- **PHP 8.4+**: typed properties, constructor promotion, Doctrine attributes, named-argument constraints (`new NotBlank(message: '...')`), no array-style constraints.
- **Login required.** `PUBLIC_ACCESS` only on `/login`, `/register`, `/forgot-password`, `/reset-password`, `/verify-email`, `/2fa`.
- **CSRF**: `framework.csrf_protection` with stateless tokens (`submit`, `authenticate`, `logout`). Form types set `csrf_protection: false`; `security.yaml form_login` has `enable_csrf: false` (SameOriginCsrfTokenManager incompatible without Stimulus/UX).
- **Password rules** (client + server): min 8 chars, one uppercase, one lowercase, one digit. Hashed via `UserPasswordHasherInterface`.
- **Anti-bot**: login form has hidden honeypot fields (`website`, `confirm_email`). `HoneypotSubscriber` checks at priority 20 and 403s + Discord notify if filled.
- **2FA (TOTP)**: `scheb/2fa-bundle` + `scheb/2fa-totp`. Secret stored encrypted via `TotpEncryptionListener` (Doctrine preFlush/postLoad). QR codes via `endroid/qr-code-bundle`. `TotpEncryptionService` uses `APP_SECRET`.
- **Login success**: `LoginSuccessHandler` sets `_login_success` session flag, used by dashboard to show login overlay.
- **Avatar uploads**: `public/uploads/avatars/`, max 2 MB, JPEG/PNG/WebP only. Old file removed on replace.

## Database

- `var/data.db` (SQLite). **No naming strategy** — entities use explicit `#[ORM\Table(name: "lowercase_name")]` (SQLite on Windows is case-insensitive for table names).
- Tables: `user`, `kanji`, `review_log`.

## SM-2 (SrsService)

- quality: 0-5, EF starts at 2.5 (min 1.3)
- quality < 3: reset (repetitions=0, interval=1)
- quality >= 3: intervals are 1, 6, then `round(interval * EF)`; repetitions++

## Timezone

- `User.timezone` nullable, `getEffectiveTimezone()` falls back to `date_default_timezone_get()`.
- `UserRepository`, `KanjiRepository`, `ReviewLogRepository` methods accept `$timezone` param (default `'UTC'`). Dashboard and profile controllers pass `$user->getEffectiveTimezone()`.
- `ProfileFormType` and `RegistrationFormType` include timezone select.

## CSS

- Pure modular CSS in `public/css/`:
  - `base.css` — design tokens (border-radius: 0 on all)
  - `dark.css` — `[data-theme="dark"]` overrides (primary: `#FF453A`)
  - `layout.css`, `components/{sidebar,topbar,mobile-nav,ui}.css`, `pages/{auth,dashboard,profile,review}.css`
- Font: Inter (body via Google), Times New Roman serif (headings). Icons: Bootstrap Icons CDN.
- Dark theme default (`localStorage.getItem('theme') || 'dark'`), toggle in sidebar.
- Sidebar collapsed: `data-sb` attribute on `<body>` + localStorage. Review page starts collapsed.
- All hardcoded `rgba(r,g,b,x)` replaced with `color-mix(in srgb, var(--color-primary) X%, transparent)` for theme adaptability.

## Architecture

- `src/Entity/{Kanji,ReviewLog,User}.php`
- `src/Repository/{Kanji,ReviewLog,User}Repository.php`
- `src/Service/SrsService.php` (SM-2), `DiscordNotifier.php`, `TotpEncryptionService.php`
- `src/Controller/{Dashboard,Review,Kanji,Profile,Registration,ResetPassword,Security,TwoFactor}Controller.php`
- `src/Form/{RegistrationForm,ProfileForm,ForgotPassword,ResetPassword,TwoFactorSetup,TwoFactorVerify}FormType.php`
- `src/Security/LoginSuccessHandler.php`
- `src/EventSubscriber/HoneypotSubscriber.php`
- `src/Doctrine/TotpEncryptionListener.php`
- `src/Command/{SeedKanjiCommand,CreateAdminCommand}.php`
- `src/Data/KanjiData.php` — seeded kanji data (581 entries)
- No tests directory exists.

## Skills

- `.agents/skills/bic-design/` — Bic blue borderless design system (Hermes style)
- `.agents/skills/interface-design/` (locked), `design-system/` (locked), `web-design-guidelines/` (locked)
