# VersionTracker

VersionTracker is a Laravel 13 application that centralizes software versions, releases, and security information. The project provides a modern Filament 5 admin panel (incl. Analytics Dashboard) as well as a public Vue frontend with timeline visualization.

## Features

- **Filament Admin** with approval workflow, Analytics Dashboard, and Audit Tools
- **Release Timeline SPA** (Vue 3 + Vite) including software filter
- **Demo Data & User** for an immediately populated showcase
- **Tailwind v4** Design System with custom Filament theme

## Stack

- PHP 8.3 · Laravel 13 · Livewire 4 · Filament 5
- MariaDB/MySQL/PostgreSQL/SQLite (Default: SQLite)
- Node 22.18+ · Vite 8 · Vue 3 · Vue Router 5 · Vue I18n 11 · Tailwind CSS 4

## Requirements

- PHP >= 8.3 + Composer 2.x
- Node.js >= 22.18 + npm 10
- SQLite (default) or an alternative database

## Installation

1. **Clone Repository**
   ```bash
   git clone https://github.com/<your-org>/versiontracker.git
   cd versiontracker
   ```

2. **Install the Application**
   ```bash
   composer run setup
   ```
   This creates the SQLite database, generates the application key, runs migrations, installs locked frontend dependencies, and builds the assets.

3. **Start Application**
   ```bash
   composer run dev
   ```
   App available at `http://localhost:8000`. The Filament panel is at `/admin`.

For demo data, run `php artisan db:seed` after setup.

## Demo Accounts

| Environment | User              | Password  |
| ----------- | ----------------- | --------- |
| Filament Admin | `demo@example.com` | `password` |

## Frontend Access

- Public Timeline SPA: `/timeline`
- API Endpoints: `/api/public/*` (e.g. `/api/public/timeline`)

## Development Workflows

- **Code Style**: `vendor/bin/pint --dirty`
- **Tests**: `php artisan test`
- **Vite Dev Server**: `npm run dev`
- **Build**: `npm run build`
- **Local App Stack**: `composer run dev`

## Self-Hosting with Docker

Each `v0.x.y` GitHub release publishes a container image at `ghcr.io/soerennb/version-tracker`. Pin a concrete release tag in production rather than using `latest`.

### Existing reverse proxy or local network

```bash
git clone https://github.com/soerennb/version-tracker.git
cd version-tracker
./install.sh install
```

Choose `N` for Caddy, then enter the release tag (for example `v0.1.0`) and the exposed HTTP port. Point your existing reverse proxy at this port and configure `TRUSTED_PROXIES` in `.env.docker` with the proxy address.

### Public server with automatic HTTPS

```bash
git clone https://github.com/soerennb/version-tracker.git
cd version-tracker
./install.sh install
```

Choose `Y` for Caddy, provide a domain whose DNS already points to the server, and provide an ACME email address. Ports 80 and 443 must be free. The installer generates secrets, initializes MariaDB, asks whether to load demo data, and otherwise creates the first administrator interactively.

### Updating

```bash
./install.sh update
```

Enter the next release tag and the active proxy mode. The update pulls the image, runs database migrations, rebuilds Laravel caches, and restarts the application. Back up both the MariaDB volume and the `app_storage` volume before upgrades.

The base Compose file remains internal-only. For manual operation, use either `compose.proxy.yml` for a direct HTTP port or `compose.caddy.yml` for Caddy; always pass `--env-file .env.docker`.

## License

MIT – see `LICENSE`.
