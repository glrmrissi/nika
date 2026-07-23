# nika

Kanji learning app with SM-2 SRS, built with Symfony 8.1 + PHP 8.4+ + SQLite.

## Stack

- **Framework:** Symfony 8.1
- **Language:** PHP 8.4+
- **Database:** SQLite (via Doctrine ORM)
- **Auth:** form_login, email verification, password reset, TOTP 2FA (scheb/2fa)
- **Frontend:** Twig, modular CSS, vanilla JS
- **SRS:** SM-2 algorithm

## Features

- Kanji browser with JLPT N5–N1 (1091 kanji)
- Spaced repetition review (SM-2)
- Dashboard with SVG activity heatmap
- Profile with README (Markdown)
- TOTP two-factor authentication
- Email verification & password reset
- Dark theme by default

## Setup

```bash
cp .env.example .env
composer install
php bin/console app:kanji:seed
php bin/console app:admin:create
php bin/console doctrine:migrations:migrate
php bin/console cache:clear
php -S localhost:8000 -t public
```

## Routes

All `#[Route]` attributes except `/2fa` and `/2fa_check` (YAML). Login, register, forgot-password, reset-password, verify-email are public.

## Conventions

- No code comments. English names everywhere.
- PHP 8.4+ typed properties, constructor promotion, Doctrine attributes.
- CSRF disabled on form_login and 2FA forms.
- SM-2: quality 0–5, EF starts at 2.5 (min 1.3).

## License

MIT
