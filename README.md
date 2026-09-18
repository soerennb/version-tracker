# VersionTracker

VersionTracker is a self-hosted release intelligence application for tracking software versions, release notes, support windows, dependencies, and security posture. It combines a public Vue frontend with a Filament administration panel for editorial workflows, governance, integrations, and operations.

## Capabilities

### Public release intelligence

- Product and release catalog with published-version visibility
- Localized release notes and application copy in German and English
- Filterable release timeline with product, support, security, date, and text filters
- Global search across products, releases, and security advisories
- Release comparison with notes, attachments, advisories, and dependency changes
- Lifecycle, support, EOL, and LTS information
- Public release attachments and safe downloads
- RSS release feed at `/feed/releases.xml`

Only published content is exposed publicly. Catalog, search, products, timeline, security, and comparison modules can be enabled or disabled independently through runtime settings.

### Security and compliance

- Public security center with advisory details, severity, status, CVSS, exploitability, affected releases, and fixed versions
- Vulnerability and dependency management in the admin panel and API
- Dependency map and impact analysis for software, releases, and vulnerabilities
- SBOM ingestion for CycloneDX and SPDX documents
- SBOM component normalization, hash/license handling, and vulnerability findings
- OSV enrichment with optional EPSS and CISA KEV risk intelligence
- Release-readiness checks for content, security, SBOM, dependencies, attachments, and lifecycle data
- Expiring readiness exceptions with ownership and audit history

### Governance and operations

- Filament 5 administration panel with analytics, work queues, and security dashboards
- Draft, approve, publish, and reject workflow for releases
- Readiness scores, review history, audit diffs, optional four-eyes approval, and controlled readiness overrides
- GitHub release and tag import with queued, idempotent synchronization and sync status reporting
- Deployment logbook with environments, approvals, lifecycle events, rollback, correction, and external-reference idempotency
- CSV exports for versions, software, audit logs, and deployments
- PDF version exports and per-release JSON compliance packages
- Configurable application, access, notification, governance, operations, GitHub, and security settings

### Accounts and integrations

- Open, invitation-only, or disabled registration modes
- Email verification, password reset, invitations, active-user controls, and session management
- Admin, editor, and viewer roles with additional permission abilities
- In-app and email notifications for approvals, releases, security alerts, fixes, and upcoming EOL events
- Product subscriptions for release, security, EOL, or all event types
- Scoped Laravel Sanctum REST API for content, governance, SBOM, impact analysis, deployments, notifications, subscriptions, and exports
- Authenticated Laravel MCP server for structured content operations, approval workflows, attachment uploads, SBOM ingestion, and readiness checks

### Platform and hardening

- Runtime feature flags and configurable public copy, locales, and support links
- Background queues for notifications and imports
- Scheduled GitHub synchronization and lifecycle alerts
- API, authentication, and verification rate limits
- Security headers, trusted-host and trusted-proxy controls, upload restrictions, token expiry, and revocation
- Health endpoint at `/up` for local and container deployments

## Stack

- PHP 8.4.1+ · Laravel 13 · Livewire 4 · Filament 5
- Laravel Sanctum · Laravel MCP · Spatie Laravel Settings
- Laravel Excel · Dompdf · OpenAPI annotations
- MariaDB, MySQL, PostgreSQL, or SQLite (SQLite is the default)
- Node.js 22.18+ · npm 10 · Vite 8 · Vue 3 · Vue Router 5 · Vue I18n 11 · Tailwind CSS 4

| Runtime context | PHP | Node.js |
| --------------- | --- | ------- |
| CI matrix | 8.4 and 8.5 | 24 |
| Local development | >= 8.4.1 | >= 22.18 |
| Production image | 8.5 | 26 |

## Requirements

- PHP >= 8.4.1 and Composer 2.x
- Node.js >= 22.18.0 and npm 10
- SQLite for the default setup, or a configured MariaDB, MySQL, or PostgreSQL database

## Local installation

Clone the repository and run the standard setup workflow:

```bash
git clone https://github.com/soerennb/version-tracker.git
cd version-tracker
composer run setup
```

`composer run setup` creates `.env` and the default SQLite database, generates the application key, runs migrations, installs locked frontend dependencies, and builds the assets.

On a fresh database, create the first administrator interactively:

```bash
php artisan app:install --no-demo
```

Start the local application stack:

```bash
composer run dev
```

This starts the Laravel server, queue listener, log viewer, and Vite development server. The public application is available at `http://localhost:8000`; the Filament panel is available at `/admin`.

### Demo data

For a local evaluation installation, use the demo mode on a fresh database instead of `--no-demo`:

```bash
php artisan app:install --demo
```

The command creates the demo dataset and prints a newly generated demo password once:

| User | Password |
| ---- | -------- |
| `demo@example.com` | generated during setup |

The demo profile is intended for local evaluation only. Generic `php artisan db:seed` calls are rejected so that demo credentials cannot be created accidentally. A marked demo installation can be rebuilt explicitly with `php artisan app:install --reset-demo --force`.

### Native single-environment installation

Tagged releases also provide a prepared native bundle with Composer dependencies and compiled frontend assets. It requires PHP 8.4.1 or newer and a configured web server, but no Docker, Composer, or Node.js on the target host:

```bash
VERSION=v0.2.0
curl -fsSLO "https://github.com/soerennb/version-tracker/releases/download/${VERSION}/versiontracker-native-${VERSION}.tar.gz"
curl -fsSLO "https://github.com/soerennb/version-tracker/releases/download/${VERSION}/versiontracker-native-${VERSION}.tar.gz.sha256"
sha256sum --check "versiontracker-native-${VERSION}.tar.gz.sha256"
tar -xzf "versiontracker-native-${VERSION}.tar.gz"
cd "versiontracker-native-${VERSION}"
./native-install.sh install --url https://tracker.example.com
```

The native installer defaults to SQLite, supports an already configured MySQL, MariaDB, or PostgreSQL connection, and prints a one-time setup token for `/install`. It does not modify Nginx/Apache, PHP-FPM, systemd, or cron configuration; point the web server at `public/` and run the queue worker and scheduler according to your host's process manager.

## Application access

| Surface | URL | Purpose |
| ------- | --- | ------- |
| Public application | `/` | Vue SPA with home, catalog, timeline, search, security, release, comparison, and account views |
| Product catalog | `/products` | Browse and filter products and their published releases |
| Release intelligence | `/timeline`, `/releases/:id`, `/products/:productId/compare` | Explore, inspect, and compare published releases |
| Security center | `/security`, `/security/:id` | Browse and inspect public security advisories |
| Account area | `/account` | Notifications, subscriptions, invitations, and account actions |
| Filament admin | `/admin` | Manage content, governance, security, users, deployments, and settings |
| Public JSON API | `/api/public/*` | Read-only overview, runtime, catalog, search, timeline, comparison, and security data |
| Public RSS feed | `/feed/releases.xml` | Published release updates in RSS 2.0 format |
| Authenticated REST API | `/api/*` | Manage resources and run governance, SBOM, deployment, impact, audit, and export workflows |
| Authenticated MCP server | `/mcp/versiontracker` | Automate VersionTracker through the Laravel MCP interface |

The public API and frontend expose published data only and are protected by API throttling. Public API modules follow the same runtime feature flags as the frontend.

### API authentication

Authenticated REST and MCP requests use Laravel Sanctum bearer tokens:

```http
Authorization: Bearer <token>
Accept: application/json
```

Administrators create tokens under **Admin → API Tokens**. Tokens can be scoped to REST or MCP access, receive granular abilities, expire, and be revoked. The token secret is shown only when it is created.

## Development workflows

```bash
# Run the complete local stack
composer run dev

# Run the frontend development server only
npm run dev

# Build frontend assets
npm run build

# Format changed PHP files
vendor/bin/pint --dirty

# Run the PHPUnit suite
php artisan test
```

The application uses database-backed queues and scheduling in production. The Docker deployment runs separate application, worker, and scheduler services.

## Self-hosting with Docker

Every `v0.x.y` GitHub release publishes a multi-platform container image (`linux/amd64` and `linux/arm64`) at `ghcr.io/soerennb/version-tracker`, a Docker deployment bundle, and a Dockerless native installation bundle. Use a concrete release tag or digest for production; `latest` is intended for evaluation only. The Docker bundle pins the MariaDB and Caddy support images by tag and digest.

### Deployment bundle

Download the exact bundle and checksum from a GitHub Release, verify the archive, and unpack it:

```bash
VERSION=v0.2.0
curl -fsSLO "https://github.com/soerennb/version-tracker/releases/download/${VERSION}/versiontracker-deploy-${VERSION}.tar.gz"
curl -fsSLO "https://github.com/soerennb/version-tracker/releases/download/${VERSION}/versiontracker-deploy-${VERSION}.tar.gz.sha256"
sha256sum --check "versiontracker-deploy-${VERSION}.tar.gz.sha256"
tar -xzf "versiontracker-deploy-${VERSION}.tar.gz"
cd "versiontracker-deploy-${VERSION}"
```

### Installer

The installer supports an existing reverse proxy and a Caddy mode with automatic HTTPS:

```bash
./install.sh install
./install.sh update
./install.sh status
./install.sh backup
```

For unattended installations, select a concrete version and pipe the administrator password through standard input:

```bash
printf '%s\n' 'choose-a-long-unique-password' | ./install.sh install \
  --version v0.2.0 \
  --mode proxy \
  --port 8080 \
  --admin-name 'Administrator' \
  --admin-email admin@example.com \
  --admin-password-stdin
```

The installer creates protected environment files and secrets, initializes MariaDB, starts the application, worker, and scheduler services, runs migrations, and checks `/up`. Interactive installations prepare the application and print a one-time setup token for `/install`; use `--setup cli` with `--admin-password-stdin` for fully automated provisioning. The browser setup never writes host or container environment files. The default Compose project name is `versiontracker`, so named volumes remain stable when a release bundle is unpacked into a new directory. Keep that value unless you intentionally migrate volumes.

### Multiple isolated instances

Use proxy mode and a unique instance name for staging, production, or customer environments on the same host:

```bash
./install.sh install \
  --instance staging \
  --base-dir /opt/versiontracker \
  --version v0.2.0 \
  --mode proxy \
  --port 18080 \
  --url https://staging.tracker.example.com

./install.sh list --base-dir /opt/versiontracker
./install.sh status --instance staging --base-dir /opt/versiontracker
./install.sh backup --instance staging --base-dir /opt/versiontracker
./install.sh update --instance staging --base-dir /opt/versiontracker --version v0.2.0 --yes
```

Each named instance gets its own environment file, MariaDB data, application storage, Compose project, cache prefix, session cookie, and backup directory. The proxy should route `staging.tracker.example.com` to `127.0.0.1:18080`; choose another host port for every additional instance. Caddy mode remains a single host-wide installation because it owns ports 80 and 443.

In Caddy mode, ports 80 and 443 must be available and DNS must already point to the server. In proxy mode, unattended installations can set the public URL and proxy trust explicitly:

```bash
printf '%s\n' 'choose-a-long-unique-password' | ./install.sh install \
  --version v0.2.0 \
  --mode proxy \
  --port 8080 \
  --url https://tracker.example.com \
  --trusted-hosts tracker.example.com \
  --trusted-proxies 172.20.0.0/16 \
  --admin-name 'Administrator' \
  --admin-email admin@example.com \
  --admin-password-stdin
```

Configure the existing reverse proxy and set `TRUSTED_PROXIES` as described in the [self-hosting guide](docs/self-hosting.md), which also covers configuration, mail delivery, backup verification, restore, and rollback.

The base Compose stack consists of `app`, `db`, `worker`, and `scheduler`. The worker consumes the `notifications`, `imports`, and `default` queues; the scheduler runs Laravel's scheduled tasks. Only `app` exposes the HTTP health check because the worker and scheduler are long-running CLI services. The published image contains the Laravel package manifests, Filament assets, PHP extensions, Apache configuration, and compiled frontend assets required by the full stack. The image is tested through Compose; it is not an all-in-one SQLite container. PHP's web upload and request limits are set to 32 MB; application-level defaults allow 10 MB attachments and 20 MB SBOM documents and can be adjusted through `.env.docker`.

`./install.sh update` creates a backup containing the database, Laravel storage, environment file, a manifest, and SHA-256 checksums before pulling the new application image. It migrates before recreating the runtime services and pulls only `app`, `worker`, and `scheduler`; if migration or recreation fails, inspect the logs and use the recorded backup before attempting a rollback.

Every release also publishes `versiontracker-native-v0.x.y.tar.gz` with a matching SHA-256 file and release manifest for a single non-Docker environment. It contains the locked Composer dependencies, Laravel package cache, Filament assets, compiled frontend assets, and `native-install.sh`; Composer and Node.js are not required on the target host. Use it on a host with PHP 8.4.1 or newer and configure the web server and process manager separately.

## CI and releases

- **CI gate** validates changed areas with frontend builds, PHP tests and Pint, SQLite/MariaDB integration checks, container and Compose validation, installer checks, and backup/restore tests.
- **Security gate** runs secret scanning on every pull request and `master` push, plus dependency audits and Semgrep SAST for the relevant changes.
- **Tagged releases** matching `v0.*.*` repeat the PHP 8.4/8.5 validation matrix, build and smoke-test the extracted native bundle, publish multi-platform GHCR images with provenance and an SBOM, validate the immutable image through the complete Compose stack, run container security checks, and publish both checksummed installation archives.

See the [release guide](docs/releasing.md) for the maintainer release procedure.

## License

MIT – see [LICENSE](LICENSE).
