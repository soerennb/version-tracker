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
| CI support floor | 8.4 | 24 |
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

This creates the demo dataset and the following demo account:

| User | Password |
| ---- | -------- |
| `demo@example.com` | `password` |

The demo credentials are intentionally weak and must never be used for a production deployment.

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

Every `v0.x.y` GitHub release publishes a container image at `ghcr.io/soerennb/version-tracker` and a compact deployment bundle. Use a concrete release tag or digest for production; `latest` is intended for evaluation only.

### Deployment bundle

Download the exact bundle and checksum from a GitHub Release, verify the archive, and unpack it:

```bash
VERSION=v0.1.2
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
  --version v0.1.2 \
  --mode proxy \
  --port 8080 \
  --admin-name 'Administrator' \
  --admin-email admin@example.com \
  --admin-password-stdin
```

The installer creates protected environment files and secrets, initializes MariaDB, starts the application, worker, and scheduler services, runs migrations, and checks `/up`. In Caddy mode, ports 80 and 443 must be available and DNS must already point to the server. In proxy mode, configure the existing reverse proxy and set `TRUSTED_PROXIES` as described in the [self-hosting guide](docs/self-hosting.md), which also covers configuration, mail delivery, restore, and rollback.

## CI and releases

- **CI gate** validates changed areas with frontend builds, PHP tests and Pint, SQLite/MariaDB integration checks, container and Compose validation, installer checks, and backup/restore tests.
- **Security gate** runs secret scanning on every pull request and `master` push, plus dependency audits and Semgrep SAST for the relevant changes.
- **Tagged releases** matching `v0.*.*` repeat release validation, publish GHCR images with provenance and an SBOM, smoke-test the immutable image, run container security checks, and publish the deployment bundle with a SHA-256 checksum.

See the [release guide](docs/releasing.md) for the maintainer release procedure.

## License

MIT – see [LICENSE](LICENSE).
