# Self-hosting

VersionTracker is distributed as a versioned Docker image and a compact deployment bundle. Install a concrete GitHub release tag such as `v0.1.2`; do not substitute `latest` in a production deployment.

## Prerequisites

- A Linux server with Docker Engine and the Docker Compose plugin.
- `openssl`, `grep`, `sed`, `tar`, and `sha256sum` available on the host for the installer and backup verification.
- A public DNS record and open ports 80 and 443 when using Caddy.
- A reverse proxy and an available local HTTP port when Caddy is not used.

Download the exact deployment bundle and verify its checksum before unpacking it:

```bash
VERSION=v0.1.2
curl -fsSLO "https://github.com/soerennb/version-tracker/releases/download/${VERSION}/versiontracker-deploy-${VERSION}.tar.gz"
curl -fsSLO "https://github.com/soerennb/version-tracker/releases/download/${VERSION}/versiontracker-deploy-${VERSION}.tar.gz.sha256"
sha256sum --check "versiontracker-deploy-${VERSION}.tar.gz.sha256"
tar -xzf "versiontracker-deploy-${VERSION}.tar.gz"
cd "versiontracker-deploy-${VERSION}"
./install.sh install
```

The installer verifies Docker, generates `.env.docker` with mode `0600`, creates database secrets, records the selected deployment mode, starts MariaDB, and creates the initial administrator. The generated configuration uses the stable Compose project name `versiontracker`; this keeps named volumes (`versiontracker_db_data`, `versiontracker_app_storage`, and, in Caddy mode, the Caddy volumes) independent of the directory name. Change `COMPOSE_PROJECT_NAME` only when deliberately migrating or isolating a deployment. Demo data requires a second confirmation because it includes a public password.

Repository cloning remains supported for contributors. Operators only need the deployment bundle.

### Unattended installation

Supply all configuration options and pass only the administrator password over standard input. Do not place the password in a command-line argument, `.env.docker`, CI log, or shell history.

```bash
printf '%s\n' 'choose-a-long-unique-password' | ./install.sh install \
  --version v0.1.2 \
  --mode proxy \
  --port 8080 \
  --url https://tracker.example.com \
  --trusted-hosts tracker.example.com \
  --trusted-proxies 172.20.0.0/16 \
  --admin-name 'Administrator' \
  --admin-email admin@example.com \
  --admin-password-stdin
```

For Caddy, replace `--mode proxy --port 8080` with `--mode caddy --domain example.com --email ops@example.com`. The selected domain must already resolve to the server and ports 80 and 443 must be available.

## Deployment modes

Choose `caddy` for a public host with automatic HTTPS. Caddy receives ports 80 and 443, obtains the certificate, and forwards requests to the internal application container.

Choose `proxy` when an existing reverse proxy terminates TLS or the application is available only on a local network. The application binds `APP_PORT` on the host. Set `APP_URL`, `TRUSTED_HOSTS`, and `TRUSTED_PROXIES` in `.env.docker` to the public URL, hostname, and proxy address before exposing the service.

Important `.env.docker` settings:

| Setting                             | Purpose                                                   |
| ----------------------------------- | --------------------------------------------------------- |
| `VERSION`                           | Required exact `v0.x.y` image tag.                        |
| `IMAGE_REPOSITORY`                  | Published VersionTracker image repository.                |
| `COMPOSE_PROJECT_NAME`              | Stable namespace for named volumes; keep `versiontracker` unless migrating. |
| `MARIADB_IMAGE` / `CADDY_IMAGE`     | Tested, pinned supporting images.                         |
| `DEPLOYMENT_MODE`                   | `proxy` or `caddy`; updates reuse this choice.            |
| `APP_URL`                           | Public URL used for generated links.                      |
| `TRUSTED_HOSTS` / `TRUSTED_PROXIES` | Restrict the public hostname and trusted proxy addresses. |
| `UPLOAD_MAX_KB` / `SBOM_MAX_KB`     | Application upload limits; defaults are 10240 and 20480. |
| `SBOM_MAX_COMPONENTS`               | Maximum normalized SBOM components; default is 10000.   |
| `DB_PASSWORD` / `DB_ROOT_PASSWORD`  | Generated MariaDB credentials; keep them private.         |
| `MAIL_MAILER`                       | Mail transport; replace `log` with a production transport. |
| `MAIL_HOST` / `MAIL_PORT`           | SMTP host and port when using the SMTP transport.         |
| `MAIL_USERNAME` / `MAIL_PASSWORD`   | SMTP credentials, when required by the mail provider.     |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Sender identity for lifecycle and security alerts.      |

## Operations

Check the running containers and application health:

```bash
./install.sh status
```

The base Compose stack runs `app`, `db`, `worker`, and `scheduler`. The worker consumes the `notifications`, `imports`, and `default` queues. The scheduler runs Laravel's scheduled tasks, including the lifecycle alert command at its configured daily time (08:00 UTC by default). Only `app` has an HTTP health check; worker and scheduler health is verified by their running process. Configure a real mail transport before expecting email delivery; `MAIL_MAILER=log` is the safe default for validation and writes messages to the application log.

The container image uses PHP 8.5 with `upload_max_filesize=32M`, `post_max_size=32M`, and `memory_limit=256M`. Application defaults limit release attachments to 10 MB and SBOM documents to 20 MB; review `UPLOAD_MAX_KB`, `SBOM_MAX_KB`, and `SBOM_MAX_COMPONENTS` in `.env.docker` when changing those limits.

Back up MariaDB, Laravel storage, and the current environment file before every upgrade:

```bash
./install.sh backup
```

Backups are stored under `backups/versiontracker-<timestamp>/` with owner-only permissions. Each backup contains `database.sql`, `storage.tar.gz`, `environment.backup`, a `manifest`, and `checksums.sha256`; verify the files with `sha256sum --check --strict`. Copy backups off the server; they contain database data and credentials.

Schedule this command with the server's existing scheduler and copy its completed backup directory to independent storage. For example, run it daily through a systemd timer or cron, redirect the output to an operator-only log, and alert when the command fails. A backup retained only on the deployment host does not protect against host loss.

Upgrade by entering the next exact release tag:

```bash
./install.sh update
```

The command creates the backup first, changes `VERSION`, pulls only the new application image for `app`, `worker`, and `scheduler`, stops the old runtime, and runs database migrations before recreating the services. It refreshes Laravel caches and verifies `/up` plus the worker and scheduler processes. If image pull or Compose validation fails, the previous `VERSION` is restored. If migration or recreation fails, the runtime remains stopped and the new `VERSION` is retained so the failure can be inspected; use the backup before attempting a rollback. Inspect logs with `docker compose --project-name versiontracker --env-file .env.docker -f compose.yml -f compose.<mode>.yml logs`. Do not roll back after an irreversible migration without first restoring its backup.

## Restore

Restore only into a stopped or isolated deployment. Select the Compose file matching `DEPLOYMENT_MODE`, start MariaDB, then load the database and storage archive:

```bash
docker compose --project-name versiontracker --env-file .env.docker -f compose.yml -f compose.proxy.yml up -d db
docker compose --project-name versiontracker --env-file .env.docker -f compose.yml -f compose.proxy.yml exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < backups/versiontracker-<timestamp>/database.sql
docker compose --project-name versiontracker --env-file .env.docker -f compose.yml -f compose.proxy.yml run --rm app sh -c 'find storage -mindepth 1 -maxdepth 1 -exec rm -rf {} + && tar -xzf - -C /var/www/html' < backups/versiontracker-<timestamp>/storage.tar.gz
docker compose --project-name versiontracker --env-file .env.docker -f compose.yml -f compose.proxy.yml up -d
```

Use `compose.caddy.yml` in place of `compose.proxy.yml` for Caddy deployments. Restore the matching `environment.backup` only after reviewing its secrets and deployment settings.
