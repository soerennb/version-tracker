# Self-hosting

VersionTracker is distributed as a versioned Docker image. Install a concrete GitHub release tag such as `v0.1.0`; do not substitute `latest` in a production deployment.

## Prerequisites

- A Linux server with Docker Engine and the Docker Compose plugin.
- A public DNS record and open ports 80 and 443 when using Caddy.
- A reverse proxy and an available local HTTP port when Caddy is not used.

Clone the repository, change into it, and run the installer:

```bash
git clone https://github.com/soerennb/version-tracker.git
cd versiontracker
./install.sh install
```

The installer verifies Docker, generates `.env.docker` with mode `0600`, creates database secrets, records the selected deployment mode, starts MariaDB, and creates the initial administrator. Demo data requires a second confirmation because it includes a public password.

## Deployment modes

Choose `caddy` for a public host with automatic HTTPS. Caddy receives ports 80 and 443, obtains the certificate, and forwards requests to the internal application container.

Choose `proxy` when an existing reverse proxy terminates TLS or the application is available only on a local network. The application binds `APP_PORT` on the host. Set `APP_URL`, `TRUSTED_HOSTS`, and `TRUSTED_PROXIES` in `.env.docker` to the public URL, hostname, and proxy address before exposing the service.

Important `.env.docker` settings:

| Setting | Purpose |
| --- | --- |
| `VERSION` | Required exact `v0.x.y` image tag. |
| `DEPLOYMENT_MODE` | `proxy` or `caddy`; updates reuse this choice. |
| `APP_URL` | Public URL used for generated links. |
| `TRUSTED_HOSTS` / `TRUSTED_PROXIES` | Restrict the public hostname and trusted proxy addresses. |
| `DB_PASSWORD` / `DB_ROOT_PASSWORD` | Generated MariaDB credentials; keep them private. |

## Operations

Check the running containers and application health:

```bash
./install.sh status
```

Back up MariaDB, Laravel storage, and the current environment file before every upgrade:

```bash
./install.sh backup
```

Backups are stored under `backups/versiontracker-<timestamp>/` with owner-only permissions. Copy them off the server; they contain database data and credentials.

Upgrade by entering the next exact release tag:

```bash
./install.sh update
```

The command pulls the image, migrates the database, refreshes Laravel caches, and verifies `/up`. If the health check fails, inspect logs with `docker compose --env-file .env.docker -f compose.yml -f compose.<mode>.yml logs` and return `VERSION` to the previous tag. Do not roll back after an irreversible migration without first restoring its backup.

## Restore

Restore only into a stopped or isolated deployment. Select the Compose file matching `DEPLOYMENT_MODE`, start MariaDB, then load the database and storage archive:

```bash
docker compose --env-file .env.docker -f compose.yml -f compose.proxy.yml up -d db
docker compose --env-file .env.docker -f compose.yml -f compose.proxy.yml exec -T db sh -c 'exec mariadb -uroot -p"$MARIADB_ROOT_PASSWORD" "$MARIADB_DATABASE"' < backups/versiontracker-<timestamp>/database.sql
docker compose --env-file .env.docker -f compose.yml -f compose.proxy.yml run --rm app sh -c 'rm -rf storage/* && tar -xzf - -C /var/www/html' < backups/versiontracker-<timestamp>/storage.tar.gz
```

Use `compose.caddy.yml` in place of `compose.proxy.yml` for Caddy deployments. Restore the matching `environment.backup` only after reviewing its secrets and deployment settings.
