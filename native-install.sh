#!/usr/bin/env bash

set -euo pipefail

script_directory="$(cd -- "$(dirname -- "${BASH_SOURCE[0]}")" && pwd)"
readonly script_directory
environment_file="${script_directory}/.env"

install_url=''
install_setup_mode=''
install_admin_name=''
install_admin_email=''
install_admin_password_stdin=false
install_demo=false
install_database=''
environment_was_created=false

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
    [[ -f "$environment_file" ]] || return 0

    grep -m1 "^${1}=" "$environment_file" | cut -d= -f2- || true
}

usage() {
    cat <<'EOF'
Usage:
  ./native-install.sh install [--url URL] [--database sqlite|mysql|mariadb|pgsql]
                              [--setup web|cli] [--demo]
                              [--admin-name NAME --admin-email EMAIL --admin-password-stdin]
  ./native-install.sh doctor
  ./native-install.sh status

The native installer expects a prepared bundle with vendor/ and public/build/.
It configures the application only; web-server and process-manager setup remains
with the operator.
EOF
}

parse_install_options() {
    while [[ $# -gt 0 ]]; do
        case "$1" in
            --url)
                require_option_value "$1" "${2:-}"
                install_url="$2"
                shift 2
                ;;
            --database)
                require_option_value "$1" "${2:-}"
                install_database="$2"
                shift 2
                ;;
            --setup)
                require_option_value "$1" "${2:-}"
                install_setup_mode="$2"
                shift 2
                ;;
            --demo)
                install_demo=true
                shift
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

    if [[ -n "$install_setup_mode" && "$install_setup_mode" != web && "$install_setup_mode" != cli ]]; then
        echo 'Setup mode must be either web or cli.' >&2
        exit 1
    fi

    if [[ -n "$install_database" && "$install_database" != sqlite && "$install_database" != mysql && "$install_database" != mariadb && "$install_database" != pgsql ]]; then
        echo 'Database must be sqlite, mysql, mariadb, or pgsql.' >&2
        exit 1
    fi

    if [[ "$install_admin_password_stdin" == true && ( -z "$install_admin_name" || -z "$install_admin_email" ) ]]; then
        echo 'Administrator name and email are required with --admin-password-stdin.' >&2
        exit 1
    fi

    if [[ "$install_demo" == true && "$install_setup_mode" == web ]]; then
        echo '--demo requires CLI setup.' >&2
        exit 1
    fi
}

doctor() {
    require_command php
    require_command grep
    require_command sed

    if (( BASH_VERSINFO[0] < 5 )); then
        echo 'Bash 5 or newer is required.' >&2
        exit 1
    fi

    php -r 'exit(version_compare(PHP_VERSION, "8.4.1", ">=") ? 0 : 1);' || {
        echo 'PHP 8.4.1 or newer is required.' >&2
        exit 1
    }

    for extension in mbstring openssl pdo; do
        php -r "exit(extension_loaded('${extension}') ? 0 : 1);" || {
            echo "PHP extension is required: ${extension}" >&2
            exit 1
        }
    done

    local database_connection
    database_connection="${install_database:-$(environment_value DB_CONNECTION)}"
    database_connection="${database_connection:-sqlite}"

    case "$database_connection" in
        sqlite)
            for extension in pdo_sqlite sqlite3; do
                php -r "exit(extension_loaded('${extension}') ? 0 : 1);" || {
                    echo "PHP extension is required for ${database_connection}: ${extension}" >&2
                    exit 1
                }
            done
            ;;
        mysql|mariadb)
            php -r "exit(extension_loaded('pdo_mysql') ? 0 : 1);" || {
                echo "PHP extension is required for ${database_connection}: pdo_mysql" >&2
                exit 1
            }
            ;;
        pgsql)
            php -r "exit(extension_loaded('pdo_pgsql') ? 0 : 1);" || {
                echo "PHP extension is required for ${database_connection}: pdo_pgsql" >&2
                exit 1
            }
            ;;
        *)
            echo "Unsupported database connection: ${database_connection}" >&2
            exit 1
            ;;
    esac

    [[ -f "${script_directory}/artisan" ]] || { echo 'artisan is missing from the bundle.' >&2; exit 1; }
    [[ -f "${script_directory}/vendor/autoload.php" ]] || { echo 'vendor/autoload.php is missing; use a prepared native bundle.' >&2; exit 1; }
    [[ -d "${script_directory}/public/build" ]] || { echo 'public/build is missing; use a prepared native bundle.' >&2; exit 1; }
    [[ -w "$script_directory" ]] || { echo "Bundle directory is not writable: ${script_directory}" >&2; exit 1; }
    mkdir -p "${script_directory}/storage/app/public" "${script_directory}/storage/framework/cache/data" "${script_directory}/storage/framework/sessions" "${script_directory}/storage/framework/views" "${script_directory}/storage/logs" "${script_directory}/bootstrap/cache"
    [[ -w "${script_directory}/storage" && -w "${script_directory}/bootstrap/cache" ]] || { echo 'Storage and bootstrap/cache must be writable.' >&2; exit 1; }

    echo 'Native bundle prerequisites are valid.'
}

prepare_environment() {
    if [[ ! -f "$environment_file" ]]; then
        cp "${script_directory}/.env.example" "$environment_file"
        environment_was_created=true
    fi

    chmod 600 "$environment_file"
    set_environment_value APP_NAME VersionTracker
    set_environment_value APP_ENV production
    set_environment_value APP_DEBUG false

    if [[ -n "$install_url" ]]; then
        if ! [[ "$install_url" =~ ^https?://[^/?#[:space:]]+(/[^[:space:]]*)?$ ]]; then
            echo 'The application URL must be an absolute http:// or https:// URL without whitespace.' >&2
            exit 1
        fi
        set_environment_value APP_URL "$install_url"
    fi

    if [[ -n "$install_database" ]]; then
        set_environment_value DB_CONNECTION "$install_database"
    fi

    if [[ "$(environment_value DB_CONNECTION)" == sqlite ]]; then
        mkdir -p "${script_directory}/database"
        touch "${script_directory}/database/database.sqlite"
        chmod 600 "${script_directory}/database/database.sqlite"
        set_environment_value DB_DATABASE "${script_directory}/database/database.sqlite"
    fi

    if [[ -z "$(environment_value APP_KEY)" ]]; then
        php "${script_directory}/artisan" key:generate --force --no-interaction
    fi

    if [[ "$install_demo" == true ]]; then
        install_setup_mode=cli
    elif [[ -z "$install_setup_mode" ]]; then
        if [[ "$install_admin_password_stdin" == true ]]; then
            install_setup_mode=cli
        else
            install_setup_mode=web
        fi
    fi

    if [[ "$environment_was_created" == true || -n "$install_url" ]]; then
        case "$(environment_value APP_URL)" in
            https://*) set_environment_value SESSION_SECURE_COOKIE true ;;
            http://*) set_environment_value SESSION_SECURE_COOKIE false ;;
        esac
    fi

    if [[ "$install_setup_mode" == web && -z "$(environment_value INSTALLER_SETUP_TOKEN)" ]]; then
        set_environment_value INSTALLER_SETUP_TOKEN "$(php -r 'echo bin2hex(random_bytes(24));')"
    fi
}

install() {
    doctor >/dev/null
    prepare_environment

    php "${script_directory}/artisan" package:discover --ansi
    php "${script_directory}/artisan" migrate --force

    if [[ "$install_demo" == true ]]; then
        php "${script_directory}/artisan" app:install --demo
    elif [[ "$install_setup_mode" == cli ]]; then
        if [[ "$install_admin_password_stdin" == true ]]; then
            php "${script_directory}/artisan" app:install \
                --no-demo \
                --admin-name="$install_admin_name" \
                --admin-email="$install_admin_email" \
                --admin-password-stdin
        else
            php "${script_directory}/artisan" app:install --no-demo
        fi
    else
        php "${script_directory}/artisan" storage:link --force
        php "${script_directory}/artisan" optimize
        echo "Open $(environment_value APP_URL)/install and enter this one-time setup token:"
        environment_value INSTALLER_SETUP_TOKEN
    fi
}

status() {
    doctor
    php "${script_directory}/artisan" about --only=environment
    php "${script_directory}/artisan" migrate:status
}

case "${1:-}" in
    install)
        shift
        parse_install_options "$@"
        install
        ;;
    doctor)
        doctor
        ;;
    status)
        status
        ;;
    *)
        usage >&2
        exit 1
        ;;
esac
