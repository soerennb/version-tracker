#!/usr/bin/env bash

set -euo pipefail

readonly environment_file=.env.docker

set_environment_value() {
    local key="$1"
    local value="$2"

    if grep -q "^${key}=" "$environment_file"; then
        sed -i "s|^${key}=.*|${key}=${value}|" "$environment_file"
    else
        printf '%s=%s\n' "$key" "$value" >> "$environment_file"
    fi
}

compose() {
    docker compose --env-file "$environment_file" -f compose.yml -f "$1" "${@:2}"
}

configure_installation() {
    if [[ ! -f "$environment_file" ]]; then
        cp .env.docker.example "$environment_file"
        set_environment_value APP_KEY "base64:$(openssl rand -base64 32)"
        set_environment_value DB_PASSWORD "$(openssl rand -base64 24 | tr -d '\n')"
        set_environment_value DB_ROOT_PASSWORD "$(openssl rand -base64 24 | tr -d '\n')"
    fi

    local version
    read -r -p "Image version [$(grep '^VERSION=' "$environment_file" | cut -d= -f2)]: " version
    if [[ -n "$version" ]]; then
        set_environment_value VERSION "$version"
    fi

    local mode
    read -r -p "Use Caddy automatic HTTPS? [y/N]: " mode

    if [[ "$mode" =~ ^[Yy]$ ]]; then
        local domain
        local email
        read -r -p "Public domain: " domain
        read -r -p "ACME email: " email
        [[ -n "$domain" && -n "$email" ]] || { echo "A domain and email are required for Caddy." >&2; exit 1; }

        set_environment_value CADDY_DOMAIN "$domain"
        set_environment_value CADDY_EMAIL "$email"
        set_environment_value APP_URL "https://${domain}"
        set_environment_value TRUSTED_HOSTS "$domain"
        set_environment_value TRUSTED_PROXIES "*"
        printf '%s\n' compose.caddy.yml

        return
    fi

    local port
    read -r -p "Application port [$(grep '^APP_PORT=' "$environment_file" | cut -d= -f2)]: " port
    if [[ -n "$port" ]]; then
        set_environment_value APP_PORT "$port"
        set_environment_value APP_URL "http://localhost:${port}"
    fi

    printf '%s\n' compose.proxy.yml
}

install() {
    local compose_file
    compose_file="$(configure_installation)"

    compose "$compose_file" pull
    compose "$compose_file" up -d db
    compose "$compose_file" run --rm app php artisan app:install
    compose "$compose_file" up -d
}

update() {
    [[ -f "$environment_file" ]] || { echo "Run ./install.sh install first." >&2; exit 1; }

    local mode
    read -r -p "Use Caddy automatic HTTPS? [y/N]: " mode
    local compose_file=compose.proxy.yml
    if [[ "$mode" =~ ^[Yy]$ ]]; then
        compose_file=compose.caddy.yml
    fi

    local version
    read -r -p "New image version: " version
    [[ -n "$version" ]] || { echo "An image version is required." >&2; exit 1; }
    set_environment_value VERSION "$version"

    compose "$compose_file" pull app
    compose "$compose_file" up -d app
    compose "$compose_file" exec app php artisan migrate --force
    compose "$compose_file" exec app php artisan optimize
}

case "${1:-}" in
    install)
        install
        ;;
    update)
        update
        ;;
    *)
        echo "Usage: ./install.sh {install|update}" >&2
        exit 1
        ;;
esac
