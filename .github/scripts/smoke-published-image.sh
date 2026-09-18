#!/usr/bin/env bash

set -euo pipefail

readonly image="${IMAGE:?IMAGE must contain the published image repository}"
readonly digest="${DIGEST:?DIGEST must contain the published image digest}"
script_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
readonly script_directory
smoke_directory="$(mktemp -d)"
readonly smoke_directory
readonly compose_project="versiontracker-release-smoke"
readonly local_image="versiontracker-release-smoke:published"
readonly direct_container="versiontracker-release-direct"
readonly application_port=18080
readonly image_reference="${image}@${digest}"
readonly environment_file="${smoke_directory}/.env.docker"

compose() {
    docker compose \
        --project-name "$compose_project" \
        --env-file "$environment_file" \
        -f "${script_directory}/compose.yml" \
        -f "${script_directory}/compose.proxy.yml" \
        "$@"
}

cleanup() {
    compose down --volumes --remove-orphans >/dev/null 2>&1 || true
    docker rm --force "$direct_container" >/dev/null 2>&1 || true
    docker image rm "$local_image" >/dev/null 2>&1 || true
}

trap cleanup EXIT

docker pull "$image_reference"
docker tag "$image_reference" "$local_image"

docker run --detach --rm \
    --name "$direct_container" \
    --publish 18081:80 \
    --env APP_ENV=production \
    --env APP_DEBUG=false \
    --env APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA= \
    "$image_reference" >/dev/null

direct_healthy=false
for _ in {1..30}; do
    if curl --fail --silent http://127.0.0.1:18081/up >/dev/null; then
        direct_healthy=true
        break
    fi

    sleep 2
done

if [[ "$direct_healthy" != true ]]; then
    docker logs "$direct_container"
    exit 1
fi

docker exec "$direct_container" sh -c '
    test -s /var/www/html/bootstrap/cache/packages.php &&
    test -s /var/www/html/bootstrap/cache/services.php &&
    test -s /var/www/html/public/build/manifest.json &&
    test -s /var/www/html/public/css/filament/filament/app.css &&
    test -s /var/www/html/public/js/filament/filament/app.js &&
    test -L /var/www/html/public/storage &&
    php -r "exit(extension_loaded(\"gd\") && extension_loaded(\"intl\") && extension_loaded(\"mbstring\") && extension_loaded(\"pdo_mysql\") && extension_loaded(\"zip\") ? 0 : 1);" &&
    apachectl -t
'

cp "${script_directory}/.env.docker.example" "$environment_file"
sed -i "s|^VERSION=.*|VERSION=published|" "$environment_file"
sed -i "s|^IMAGE_REPOSITORY=.*|IMAGE_REPOSITORY=${local_image%:*}|" "$environment_file"
sed -i "s|^COMPOSE_PROJECT_NAME=.*|COMPOSE_PROJECT_NAME=${compose_project}|" "$environment_file"
sed -i "s|^VERSIONTRACKER_ENV_FILE=.*|VERSIONTRACKER_ENV_FILE=${environment_file}|" "$environment_file"
sed -i 's|^DEPLOYMENT_MODE=.*|DEPLOYMENT_MODE=proxy|' "$environment_file"
sed -i 's|^APP_URL=.*|APP_URL=http://127.0.0.1:18080|' "$environment_file"
sed -i 's|^APP_PORT=.*|APP_PORT=18080|' "$environment_file"
sed -i 's|^APP_BIND_ADDRESS=.*|APP_BIND_ADDRESS=127.0.0.1|' "$environment_file"
sed -i 's|^APP_KEY=.*|APP_KEY=base64:AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=|' "$environment_file"
sed -i 's|^DB_PASSWORD=.*|DB_PASSWORD=versiontracker|' "$environment_file"
sed -i 's|^DB_ROOT_PASSWORD=.*|DB_ROOT_PASSWORD=root|' "$environment_file"
sed -i 's|^INSTALLER_SETUP_TOKEN=.*|INSTALLER_SETUP_TOKEN=|' "$environment_file"

compose config --quiet
compose up --detach db
compose run --rm app php artisan migrate --force
printf '%s\n' 'Release Smoke Password 2026!' | compose run --rm -T app php artisan app:install \
    --no-demo \
    --admin-name='Release Smoke Administrator' \
    --admin-email=release-smoke@example.invalid \
    --admin-password-stdin
compose up --detach app worker scheduler

healthy=false
for _ in {1..30}; do
    if curl --fail --silent "http://127.0.0.1:${application_port}/up" >/dev/null; then
        healthy=true
        break
    fi

    sleep 2
done

if [[ "$healthy" != true ]]; then
    compose logs
    exit 1
fi

curl --fail --silent "http://127.0.0.1:${application_port}/" >/dev/null
curl --fail --silent "http://127.0.0.1:${application_port}/admin/login" >/dev/null
curl --fail --silent "http://127.0.0.1:${application_port}/css/filament/filament/app.css" >/dev/null
curl --fail --silent "http://127.0.0.1:${application_port}/js/filament/filament/app.js" >/dev/null

for service in worker scheduler; do
    compose ps --status running --services | grep --fixed-strings --line-regexp "$service"
done

compose exec --no-TTY app test -s /var/www/html/bootstrap/cache/packages.php
compose exec --no-TTY app test -s /var/www/html/bootstrap/cache/services.php
compose exec --no-TTY app test -L /var/www/html/public/storage

echo 'Published Docker image passed direct and full Compose smoke tests.'
