#!/usr/bin/env bash

set -euo pipefail

readonly environment_file=.env.docker
readonly backup_directory=backups

install_version=''
install_mode=''
install_port=''
install_domain=''
install_email=''
install_admin_name=''
install_admin_email=''
install_admin_password_stdin=false

set_environment_value() {
    local key="$1"
    local value="$2"

    if grep -q "^${key}=" "$environment_file"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$environment_file"
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

compose() {
    docker compose --env-file "$environment_file" -f compose.yml -f "$1" "${@:2}"
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || {
        echo "Required command is unavailable: $1" >&2
        exit 1
    }
}

is_valid_version() {
    [[ "$1" =~ ^v0\.[0-9]+\.[0-9]+$ ]]
}

usage() {
    cat <<'EOF'
Usage:
  ./install.sh install [--version v0.x.y] [--mode proxy|caddy] [--port PORT]
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
                install_version="$2"
                shift 2
                ;;
            --mode)
                install_mode="$2"
                shift 2
                ;;
            --port)
                install_port="$2"
                shift 2
                ;;
            --domain)
                install_domain="$2"
                shift 2
                ;;
            --email)
                install_email="$2"
                shift 2
                ;;
            --admin-name)
                install_admin_name="$2"
                shift 2
                ;;
            --admin-email)
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

    if [[ "$mode" =~ ^[Yy]$ || "$mode" == caddy ]]; then
        local domain="$install_domain"
        local email="$install_email"

        if [[ -z "$domain" ]]; then
            read -r -p 'Public domain: ' domain
        fi

        if [[ -z "$email" ]]; then
            read -r -p 'ACME email: ' email
        fi

        [[ -n "$domain" && -n "$email" ]] || { echo 'A domain and email are required for Caddy.' >&2; exit 1; }

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
    [[ "$port" =~ ^[0-9]+$ ]] || { echo 'Application port must be numeric.' >&2; exit 1; }
    check_port "$port"
    set_environment_value DEPLOYMENT_MODE proxy
    set_environment_value APP_PORT "$port"
    set_environment_value APP_URL "http://localhost:${port}"
    compose_file_for_mode proxy
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

install() {
    preflight

    local compose_file
    compose_file="$(configure_installation)"

    compose "$compose_file" config >/dev/null
    compose "$compose_file" pull
    compose "$compose_file" up -d db
    initialize_application "$compose_file"
    compose "$compose_file" up -d
    wait_for_health "$compose_file"
    compose "$compose_file" ps
}

update() {
    preflight

    local compose_file
    local version
    compose_file="$(configured_compose_file)"

    read -r -p 'New release image version: ' version
    is_valid_version "$version" || {
        echo 'Enter a concrete v0.x.y release tag.' >&2
        exit 1
    }
    set_environment_value VERSION "$version"

    compose "$compose_file" config >/dev/null
    compose "$compose_file" pull app
    compose "$compose_file" up -d app
    compose "$compose_file" exec -T app php artisan migrate --force
    compose "$compose_file" exec -T app php artisan optimize
    wait_for_health "$compose_file"
    compose "$compose_file" ps
}

status() {
    preflight

    local compose_file
    compose_file="$(configured_compose_file)"
    compose "$compose_file" ps
    wait_for_health "$compose_file"
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
    compose "$compose_file" exec -T db sh -c "exec mariadb-dump -uroot -p\"\$MARIADB_ROOT_PASSWORD\" \"\$MARIADB_DATABASE\"" > "$target_directory/database.sql"
    compose "$compose_file" exec -T app tar -C /var/www/html -czf - storage > "$target_directory/storage.tar.gz"
    cp "$environment_file" "$target_directory/environment.backup"
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
