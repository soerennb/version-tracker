#!/usr/bin/env bash

set -euo pipefail

readonly environment_file=.env.docker
readonly backup_directory=backups

install_version=''
install_mode=''
install_port=''
install_url=''
install_domain=''
install_email=''
install_trusted_hosts=''
install_trusted_proxies=''
install_admin_name=''
install_admin_email=''
install_admin_password_stdin=false

set_environment_value() {
    local key="$1"
    local value="$2"
    local escaped_value

    escaped_value="$(printf '%s' "$value" | sed 's/[&|\\]/\\&/g')"

    if grep -q "^${key}=" "$environment_file"; then
        sed -i "s|^${key}=.*|${key}=${escaped_value}|" "$environment_file"
    else
        printf '%s=%s\n' "$key" "$value" >> "$environment_file"
    fi
}

environment_value() {
    grep "^${1}=" "$environment_file" | cut -d= -f2-
}

compose_file_for_mode() {
    case "$1" in
        proxy)
            printf '%s\n' compose.proxy.yml
            ;;
        caddy)
            printf '%s\n' compose.caddy.yml
            ;;
        *)
            echo "DEPLOYMENT_MODE must be either proxy or caddy." >&2
            exit 1
            ;;
    esac
}

configured_project_name() {
    if [[ -n "${COMPOSE_PROJECT_NAME:-}" ]]; then
        printf '%s\n' "$COMPOSE_PROJECT_NAME"

        return
    fi

    if [[ -f "$environment_file" ]] && grep -q '^COMPOSE_PROJECT_NAME=' "$environment_file"; then
        local project_name
        project_name="$(environment_value COMPOSE_PROJECT_NAME || true)"

        if [[ -n "$project_name" ]]; then
            printf '%s\n' "$project_name"

            return
        fi

    fi

    printf '%s\n' versiontracker
}

compose() {
    docker compose --project-name "$(configured_project_name)" --env-file "$environment_file" -f compose.yml -f "$1" "${@:2}"
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || {
        echo "Required command is unavailable: $1" >&2
        exit 1
    }
}

require_option_value() {
    if [[ $# -lt 2 || -z "$2" ]]; then
        echo "Option $1 requires a value." >&2
        exit 1
    fi
}

is_valid_version() {
    [[ "$1" =~ ^v0\.[0-9]+\.[0-9]+$ ]]
}

is_valid_http_url() {
    [[ "$1" =~ ^https?://[^/?#[:space:]]+(/[^[:space:]]*)?$ ]]
}

usage() {
    cat <<'EOF'
Usage:
  ./install.sh install [--version v0.x.y] [--mode proxy|caddy] [--port PORT]
                       [--url URL] [--trusted-hosts HOSTS] [--trusted-proxies PROXIES]
                       [--domain DOMAIN --email EMAIL]
                       [--admin-name NAME --admin-email EMAIL --admin-password-stdin]
  ./install.sh update
  ./install.sh status
  ./install.sh backup

When all install options are supplied, pipe the administrator password to standard input.
EOF
}

parse_install_options() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --version)
                require_option_value "$1" "${2:-}"
                install_version="$2"
                shift 2
                ;;
            --mode)
                require_option_value "$1" "${2:-}"
                install_mode="$2"
                shift 2
                ;;
            --port)
                require_option_value "$1" "${2:-}"
                install_port="$2"
                shift 2
                ;;
            --url)
                require_option_value "$1" "${2:-}"
                install_url="$2"
                shift 2
                ;;
            --domain)
                require_option_value "$1" "${2:-}"
                install_domain="$2"
                shift 2
                ;;
            --email)
                require_option_value "$1" "${2:-}"
                install_email="$2"
                shift 2
                ;;
            --trusted-hosts)
                require_option_value "$1" "${2:-}"
                install_trusted_hosts="$2"
                shift 2
                ;;
            --trusted-proxies)
                require_option_value "$1" "${2:-}"
                install_trusted_proxies="$2"
                shift 2
                ;;
            --admin-name)
                require_option_value "$1" "${2:-}"
                install_admin_name="$2"
                shift 2
                ;;
            --admin-email)
                require_option_value "$1" "${2:-}"
                install_admin_email="$2"
                shift 2
                ;;
            --admin-password-stdin)
                install_admin_password_stdin=true
                shift
                ;;
            --help|-h)
                usage
                exit 0
                ;;
            *)
                echo "Unknown install option: $1" >&2
                usage >&2
                exit 1
                ;;
        esac
    done

    if [[ -n "$install_mode" && "$install_mode" != proxy && "$install_mode" != caddy ]]; then
        echo 'Install mode must be either proxy or caddy.' >&2
        exit 1
    fi

    if [[ -n "$install_domain$install_email" && "$install_mode" != caddy ]]; then
        echo 'Domain and ACME email require Caddy mode.' >&2
        exit 1
    fi

    if [[ -n "$install_url$install_trusted_hosts$install_trusted_proxies" && "$install_mode" == caddy ]]; then
        echo 'URL and trusted proxy options require proxy mode.' >&2
        exit 1
    fi

    if [[ "$install_admin_password_stdin" == true ]] && [[ -z "$install_admin_name" || -z "$install_admin_email" ]]; then
        echo 'Administrator name and email are required with --admin-password-stdin.' >&2
        exit 1
    fi
}

check_port() {
    local port="$1"

    if command -v ss >/dev/null 2>&1 && ss -ltnH "sport = :${port}" | grep -q .; then
        echo "Port ${port} is already in use." >&2
        exit 1
    fi
}

preflight() {
    require_command docker
    require_command openssl
    require_command grep
    require_command sed
    require_command tar
    require_command sha256sum
    docker compose version >/dev/null
    docker info >/dev/null
}

configure_installation() {
    if [[ ! -f "$environment_file" ]]; then
        cp .env.docker.example "$environment_file"
        set_environment_value APP_KEY "base64:$(openssl rand -base64 32)"
        set_environment_value DB_PASSWORD "$(openssl rand -base64 24 | tr -d '\n')"
        set_environment_value DB_ROOT_PASSWORD "$(openssl rand -base64 24 | tr -d '\n')"
    fi

    chmod 600 "$environment_file"

    local version="$install_version"
    local mode="$install_mode"

    if [[ -z "$version" ]]; then
        read -r -p 'Release image version (for example v0.1.0): ' version
    fi

    is_valid_version "$version" || {
        echo 'Enter a concrete v0.x.y release tag.' >&2
        exit 1
    }
    set_environment_value VERSION "$version"

    if [[ -z "$mode" ]]; then
        read -r -p 'Use Caddy automatic HTTPS? [y/N]: ' mode
    fi

    case "$mode" in
        y|Y|caddy)
            mode=caddy
            ;;
        n|N|proxy|'')
            mode=proxy
            ;;
        *)
            echo 'Choose either proxy or caddy mode.' >&2
            exit 1
            ;;
    esac

    if [[ "$mode" == caddy ]]; then
        local domain="$install_domain"
        local email="$install_email"

        if [[ -n "$install_url$install_trusted_hosts$install_trusted_proxies" ]]; then
            echo 'URL and trusted proxy options require proxy mode.' >&2
            exit 1
        fi

        if [[ -z "$domain" ]]; then
            read -r -p 'Public domain: ' domain
        fi

        if [[ -z "$email" ]]; then
            read -r -p 'ACME email: ' email
        fi

        [[ -n "$domain" && -n "$email" ]] || { echo 'A domain and email are required for Caddy.' >&2; exit 1; }
        [[ "$domain" =~ ^[A-Za-z0-9.-]+$ ]] || { echo 'The Caddy domain must contain only letters, numbers, dots, and hyphens.' >&2; exit 1; }
        [[ "$email" =~ ^[^[:space:]@]+@[^[:space:]@]+$ ]] || { echo 'The ACME email address is invalid.' >&2; exit 1; }

        check_port 80
        check_port 443
        set_environment_value DEPLOYMENT_MODE caddy
        set_environment_value CADDY_DOMAIN "$domain"
        set_environment_value CADDY_EMAIL "$email"
        set_environment_value APP_URL "https://${domain}"
        set_environment_value TRUSTED_HOSTS "$domain"
        set_environment_value TRUSTED_PROXIES '*'
        compose_file_for_mode caddy

        return
    fi

    local port="$install_port"

    if [[ -z "$port" ]]; then
        read -r -p 'Application port [8080]: ' port
    fi

    port="${port:-8080}"
    if ! [[ "$port" =~ ^[0-9]+$ ]] || (( 10#$port < 1 || 10#$port > 65535 )); then
        echo 'Application port must be between 1 and 65535.' >&2
        exit 1
    fi
    if [[ -n "$install_url" ]] && ! is_valid_http_url "$install_url"; then
        echo 'The application URL must be an absolute http:// or https:// URL without whitespace.' >&2
        exit 1
    fi
    check_port "$port"
    set_environment_value DEPLOYMENT_MODE proxy
    set_environment_value APP_PORT "$port"
    set_environment_value APP_URL "${install_url:-http://localhost:${port}}"

    if [[ -n "$install_trusted_hosts" ]]; then
        set_environment_value TRUSTED_HOSTS "$install_trusted_hosts"
    fi

    if [[ -n "$install_trusted_proxies" ]]; then
        set_environment_value TRUSTED_PROXIES "$install_trusted_proxies"
    fi

    compose_file_for_mode proxy
}

validate_environment() {
    local key
    local value

    for key in VERSION APP_KEY DB_DATABASE DB_USERNAME DB_PASSWORD DB_ROOT_PASSWORD DEPLOYMENT_MODE; do
        value="$(environment_value "$key" || true)"

        if [[ -z "$value" ]]; then
            echo "${key} must be set in ${environment_file}." >&2
            exit 1
        fi
    done

    case "$(environment_value DEPLOYMENT_MODE)" in
        proxy|caddy)
            ;;
        *)
            echo 'DEPLOYMENT_MODE must be either proxy or caddy.' >&2
            exit 1
            ;;
    esac
}

initialize_application() {
    local compose_file="$1"

    if [[ "$install_admin_password_stdin" == true ]]; then
        compose "$compose_file" run --rm -T app php artisan app:install \
            --no-demo \
            --admin-name="$install_admin_name" \
            --admin-email="$install_admin_email" \
            --admin-password-stdin

        return
    fi

    compose "$compose_file" run --rm app php artisan app:install
}

configured_compose_file() {
    [[ -f "$environment_file" ]] || { echo 'Run ./install.sh install first.' >&2; exit 1; }
    validate_environment

    local mode
    mode="$(environment_value DEPLOYMENT_MODE)"
    compose_file_for_mode "$mode"
}

wait_for_health() {
    local compose_file="$1"

    for _ in {1..30}; do
        if compose "$compose_file" exec -T app curl --fail --silent http://localhost/up >/dev/null; then
            echo 'Application health check passed.'

            return
        fi

        sleep 2
    done

    compose "$compose_file" logs --tail=100 app >&2
    echo 'Application health check failed.' >&2
    exit 1
}

wait_for_runtime_services() {
    local compose_file="$1"

    for _ in {1..30}; do
        if compose "$compose_file" ps --status running --services | grep -Fxq worker \
            && compose "$compose_file" ps --status running --services | grep -Fxq scheduler; then
            echo 'Queue worker and scheduler are running.'

            return
        fi

        sleep 2
    done

    compose "$compose_file" ps >&2
    compose "$compose_file" logs --tail=100 worker scheduler >&2
    echo 'Queue worker or scheduler failed to start.' >&2
    exit 1
}

install() {
    preflight

    local compose_file
    compose_file="$(configure_installation)"
    validate_environment

    compose "$compose_file" config >/dev/null
    compose "$compose_file" pull
    compose "$compose_file" up -d db
    initialize_application "$compose_file"
    compose "$compose_file" up -d
    wait_for_health "$compose_file"
    wait_for_runtime_services "$compose_file"
    compose "$compose_file" ps
}

update() {
    preflight

    local compose_file
    local current_version
    local version
    compose_file="$(configured_compose_file)"
    current_version="$(environment_value VERSION)"

    read -r -p 'New release image version: ' version
    is_valid_version "$version" || {
        echo 'Enter a concrete v0.x.y release tag.' >&2
        exit 1
    }
    if [[ "$version" == "$current_version" ]]; then
        echo "Version is already ${current_version}." >&2
        exit 1
    fi

    local backup_path
    backup_path="$(backup)"
    echo "Backup created at ${backup_path}."

    set_environment_value VERSION "$version"

    if ! compose "$compose_file" config >/dev/null; then
        set_environment_value VERSION "$current_version"
        echo "Compose validation failed; VERSION restored to ${current_version}." >&2
        exit 1
    fi

    if ! compose "$compose_file" pull app worker scheduler; then
        set_environment_value VERSION "$current_version"
        echo "Image pull failed; VERSION restored to ${current_version}." >&2
        exit 1
    fi

    compose "$compose_file" stop app worker scheduler

    if ! compose "$compose_file" run --rm --no-deps app php artisan migrate --force; then
        echo "Migration failed after the runtime was stopped. VERSION remains ${version}; inspect the failure and restore from ${backup_path} if needed." >&2
        exit 1
    fi

    if ! compose "$compose_file" up -d --force-recreate app worker scheduler; then
        echo "Runtime recreation failed after migration. VERSION remains ${version}; restore from ${backup_path} if needed." >&2
        exit 1
    fi

    wait_for_health "$compose_file"
    wait_for_runtime_services "$compose_file"
    compose "$compose_file" exec -T app php artisan optimize
    compose "$compose_file" ps
}

status() {
    preflight

    local compose_file
    compose_file="$(configured_compose_file)"
    compose "$compose_file" ps
    wait_for_health "$compose_file"
    wait_for_runtime_services "$compose_file"
}

backup() {
    preflight

    local compose_file
    local timestamp
    local target_directory
    compose_file="$(configured_compose_file)"
    timestamp="$(date -u +%Y%m%dT%H%M%SZ)"
    target_directory="${backup_directory}/versiontracker-${timestamp}"

    umask 077
    mkdir -p "$target_directory"
    compose "$compose_file" exec -T db sh -c "exec mariadb-dump --single-transaction --quick --routines --events --triggers -uroot -p\"\$MARIADB_ROOT_PASSWORD\" \"\$MARIADB_DATABASE\"" > "$target_directory/database.sql"
    compose "$compose_file" exec -T app tar -C /var/www/html -czf - storage > "$target_directory/storage.tar.gz"
    cp "$environment_file" "$target_directory/environment.backup"
    chmod 600 "$target_directory/database.sql" "$target_directory/storage.tar.gz" "$target_directory/environment.backup"
    {
        printf 'created_at=%s\n' "$timestamp"
        printf 'version=%s\n' "$(environment_value VERSION)"
        printf 'deployment_mode=%s\n' "$(environment_value DEPLOYMENT_MODE)"
        printf 'project_name=%s\n' "$(configured_project_name)"
    } > "$target_directory/manifest"
    chmod 600 "$target_directory/manifest"
    sha256sum "$target_directory/database.sql" "$target_directory/storage.tar.gz" "$target_directory/environment.backup" > "$target_directory/checksums.sha256"
    chmod 600 "$target_directory/checksums.sha256"
    printf '%s\n' "$target_directory"
}

case "${1:-}" in
    install)
        shift
        parse_install_options "$@"
        install
        ;;
    update)
        update
        ;;
    status)
        status
        ;;
    backup)
        backup
        ;;
    *)
        usage >&2
        exit 1
        ;;
esac
