# nika

Kanji learning app with SM-2 spaced repetition, built with Symfony 8.1 + PHP 8.4+ + SQLite.

## Stack

- **Framework:** Symfony 8.1
- **Language:** PHP 8.4+
- **Database:** SQLite (Doctrine ORM)
- **Auth:** form_login, email verification, password reset, TOTP 2FA (scheb/2fa)
- **Frontend:** Twig, modular CSS, vanilla JS
- **SRS:** SM-2 algorithm

## Features

- Kanji browser covering JLPT N5 through N1 (1091 kanji)
- Spaced repetition review with SM-2 scheduling
- Dashboard with SVG activity heatmap
- Public profile with Markdown readme
- TOTP two-factor authentication
- Email verification and password reset
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

## License

MIT
