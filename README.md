# VersionTracker

VersionTracker is a Laravel 13 application that centralizes software versions, releases, and security information. The project provides a modern Filament 5 admin panel (incl. Analytics Dashboard) as well as a public Vue frontend with timeline visualization.

## Features

- **Filament Admin** with approval workflow, Analytics Dashboard, and Audit Tools
- **Release Timeline SPA** (Vue 3 + Vite) including software filter
- **Demo Data & User** for an immediately populated showcase
- **Tailwind v4** Design System with custom Filament theme

## Stack

- PHP 8.3 · Laravel 13 · Livewire 4 · Filament 5
- MySQL/PostgreSQL/SQLite (Default: SQLite)
- Node 20+ · Vite 8 · Vue 3 · Vue Router 5 · Vue I18n 11 · Tailwind CSS 4

## Requirements

- PHP >= 8.3 + Composer 2.x
- Node.js >= 20 + npm 10
- SQLite (default) or an alternative database

## Installation

1. **Clone Repository**
   ```bash
   git clone https://github.com/<your-org>/versiontracker.git
   cd versiontracker
   ```

2. **Install Composer Dependencies**
   ```bash
   composer install
   ```

3. **Prepare Environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```
   - For SQLite: `touch database/database.sqlite` and leave `DB_CONNECTION=sqlite` in `.env`.
   - For MySQL/Postgres: set appropriate `DB_*` variables.

4. **Run Migrations & Seeders**
   ```bash
   php artisan migrate --seed
   ```
   This generates demo data including users & releases.

5. **Load Front-End Dependencies**
   ```bash
   npm install
   npm run dev
   ```
   For production: `npm run build`.

6. **Start Application**
   ```bash
   php artisan serve
   ```
   App available at `http://localhost:8000`. The Filament panel is at `/admin`.

## Demo Accounts

| Environment | User              | Password  |
| ----------- | ----------------- | --------- |
| Filament Admin | `demo@example.org` | `password` |

## Frontend Access

- Public Timeline SPA: `/timeline`
- API Endpoints: `/api/public/*` (e.g. `/api/public/timeline`)

## Development Workflows

- **Code Style**: `vendor/bin/pint --dirty`
- **Tests**: `php artisan test`
- **Vite Dev Server**: `npm run dev`
- **Build**: `npm run build`
- **Local App Stack**: `composer run dev`

## Deployment Notes

- `php artisan migrate --force`
- `npm run build` + Deploy assets under `public/build`
- Adapt `.env` to production environment (APP_URL, DB, QUEUE_CONNECTION etc.)
- `php artisan storage:link` if not already done

## License

MIT – see `LICENSE`.
