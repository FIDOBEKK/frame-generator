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
- Optional AI background removal for uploaded person photo (Python `rembg` script)
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

2. (Optional but recommended) Install background removal dependency

```bash
python3 -m venv .venv
.venv/bin/python -m pip install --upgrade pip
.venv/bin/python -m pip install rembg
```

3. Environment + app key

```bash
cp .env.example .env
php artisan key:generate
```

4. Database + seed

```bash
php artisan migrate --seed
```

5. Build assets (or run dev server)

```bash
npm run build
# or
npm run dev
```

6. Start app

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

## Environment options (background removal)

You can customize/remotely disable background removal:

```env
FRAME_GENERATOR_BG_REMOVAL_ENABLED=true
FRAME_GENERATOR_PYTHON_BIN=.venv/bin/python
FRAME_GENERATOR_BG_REMOVAL_TIMEOUT=60
```

If `rembg` is unavailable or the script fails, the app gracefully falls back to compositing the original uploaded photo.

## Notes

- Generated JPG dimensions match the background image dimensions.
- Preview coordinates are mapped from on-screen preview size to actual background pixel size.
- Background preview is served from local storage via route.
- Registration is disabled to keep auth admin-focused for this MVP.
