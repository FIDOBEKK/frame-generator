# Frame Generator (Laravel MVP)

A Laravel MVP for generating a LinkedIn-ready framed profile image.

## Features

- Public flow (no user account required):
  - Upload photo
  - Adjust placement with drag + x/y/scale controls
  - Generate and download JPG
- Admin-only login for managing one background image
- Single background setting stored in database (`app_settings` table)
- Server-side image compositing with PHP GD
- Upload validation (type, size, transform values)
- Privacy cleanup:
  - Temporary uploaded user photo is deleted after generation
  - Temporary generated JPG file is deleted after response is sent

## Tech

- Laravel 12
- Blade + Tailwind + Alpine.js
- Laravel Breeze (auth for admin)
- SQLite default setup
- PHPUnit feature tests

## Routes

### Public
- `GET /` upload + preview page
- `POST /generate` generate final JPG download

### Admin (auth required)
- `GET /admin/background` manage the single background image
- `POST /admin/background` upload/replace background image

## Local setup

1. Install dependencies

```bash
composer install
npm install
```

2. Environment + app key

```bash
cp .env.example .env
php artisan key:generate
```

3. Database + seed

```bash
php artisan migrate --seed
```

4. Build assets (or run dev server)

```bash
npm run build
# or
npm run dev
```

5. Start app

```bash
php artisan serve
```

## Default admin login

Seeded by `DatabaseSeeder`:

- Email: `admin@example.com`
- Password: `password`

Change immediately in real environments.

## Testing

Run all tests:

```bash
php artisan test --compact
```

Run only frame generation tests:

```bash
php artisan test --filter=FrameGenerationTest --compact
```

## Notes

- Generated JPG dimensions match the background image dimensions.
- Background preview is served from local storage via route.
- Registration is disabled to keep auth admin-focused for this MVP.
