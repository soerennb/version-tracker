#!/usr/bin/env bash

set -euo pipefail

repository_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")/../.." && pwd)"
readonly repository_directory
readonly version="${VERSION:?VERSION must contain a v0.x.y release tag}"
readonly output_directory="${1:-${repository_directory}/release}"

if ! [[ "$version" =~ ^v0\.[0-9]+\.[0-9]+$ ]]; then
    echo 'VERSION must match v0.x.y.' >&2
    exit 1
fi

for command_name in cp git gzip php sha256sum tar; do
    if ! command -v "$command_name" >/dev/null 2>&1; then
        echo "Required command is unavailable: ${command_name}" >&2
        exit 1
    fi
done

require_file() {
    if [[ ! -f "$1" ]]; then
        echo "Required release file is missing: $1" >&2
        exit 1
    fi
}

require_directory() {
    if [[ ! -d "$1" ]]; then
        echo "Required release directory is missing: $1" >&2
        exit 1
    fi
}

require_file "${repository_directory}/artisan"
require_file "${repository_directory}/composer.json"
require_file "${repository_directory}/composer.lock"
require_file "${repository_directory}/.env.example"
require_file "${repository_directory}/native-install.sh"
require_file "${repository_directory}/LICENSE"
require_file "${repository_directory}/README.md"
require_file "${repository_directory}/docs/self-hosting.md"
require_file "${repository_directory}/bootstrap/cache/packages.php"
require_file "${repository_directory}/bootstrap/cache/services.php"
require_file "${repository_directory}/public/build/manifest.json"
require_file "${repository_directory}/public/css/filament/filament/app.css"
require_file "${repository_directory}/public/js/filament/filament/app.js"
require_file "${repository_directory}/vendor/autoload.php"
require_directory "${repository_directory}/public/build"
require_directory "${repository_directory}/vendor"

mkdir -p "$output_directory"

staging_directory="$(mktemp -d)"
package_name="versiontracker-native-${version}"
package_directory="${staging_directory}/${package_name}"
archive="${output_directory}/${package_name}.tar.gz"
checksum_file="${output_directory}/${package_name}.tar.gz.sha256"

cleanup() {
    rm -rf -- "$staging_directory"
}

trap cleanup EXIT

mkdir -p "$package_directory"

cp -a \
    "${repository_directory}/app" \
    "${repository_directory}/artisan" \
    "${repository_directory}/bootstrap" \
    "${repository_directory}/config" \
    "${repository_directory}/database" \
    "${repository_directory}/docs/self-hosting.md" \
    "${repository_directory}/.env.example" \
    "${repository_directory}/LICENSE" \
    "${repository_directory}/README.md" \
    "${repository_directory}/resources" \
    "${repository_directory}/routes" \
    "${repository_directory}/vendor" \
    "${repository_directory}/composer.json" \
    "${repository_directory}/composer.lock" \
    "${repository_directory}/native-install.sh" \
    "${repository_directory}/public" \
    "$package_directory/"

find "$package_directory/database" -maxdepth 1 -type f -name '*.sqlite' -delete
rm -f "$package_directory/public/storage" "$package_directory/public/hot"
find "$package_directory/bootstrap/cache" -maxdepth 1 -type f \
    ! -name packages.php \
    ! -name services.php \
    ! -name settings.php \
    -delete

mkdir -p \
    "${package_directory}/storage/app/private" \
    "${package_directory}/storage/app/public" \
    "${package_directory}/storage/framework/cache/data" \
    "${package_directory}/storage/framework/sessions" \
    "${package_directory}/storage/framework/views" \
    "${package_directory}/storage/logs" \
    "${package_directory}/bootstrap/cache"

composer_lock_sha256="$(sha256sum "${repository_directory}/composer.lock" | cut -d' ' -f1)"
frontend_manifest_sha256="$(sha256sum "${repository_directory}/public/build/manifest.json" | cut -d' ' -f1)"
commit_sha="$(git -C "$repository_directory" rev-parse "${GITHUB_SHA:-HEAD}")"

if [[ -n "${SOURCE_DATE_EPOCH:-}" ]]; then
    source_date_epoch="$SOURCE_DATE_EPOCH"
else
    source_date_epoch="$(git -C "$repository_directory" log -1 --format=%ct "$commit_sha")"
fi

if ! [[ "$source_date_epoch" =~ ^[0-9]+$ ]]; then
    echo 'SOURCE_DATE_EPOCH must be a Unix timestamp.' >&2
    exit 1
fi

php_version="$(php -r 'echo PHP_VERSION;')"

cat > "${package_directory}/release-manifest.json" <<EOF
{
    "artifact": "${package_name}",
    "version": "${version}",
    "commit": "${commit_sha}",
    "source_date_epoch": ${source_date_epoch},
    "php_build_version": "${php_version}",
    "composer_lock_sha256": "${composer_lock_sha256}",
    "frontend_manifest_sha256": "${frontend_manifest_sha256}"
}
EOF

tar \
    --create \
    --use-compress-program='gzip -n' \
    --file "$archive" \
    --sort=name \
    --mtime="@${source_date_epoch}" \
    --owner=0 \
    --group=0 \
    --numeric-owner \
    --directory "$staging_directory" \
    "$package_name"

(cd "$output_directory" && sha256sum "$(basename "$archive")" > "$(basename "$checksum_file")")

printf 'Native release archive: %s\n' "$archive"
printf 'Native release checksum: %s\n' "$checksum_file"
