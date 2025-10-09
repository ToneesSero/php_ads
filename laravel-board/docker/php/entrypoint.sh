#!/bin/bash
set -euo pipefail

project_root="/var/www/html"
laravel_marker="${project_root}/artisan"

if [ ! -f "$laravel_marker" ]; then
    echo "[entrypoint] Laravel project not detected. Bootstrapping via composer create-project..."
    tmpdir="$(mktemp -d)"
    if ! composer create-project --prefer-dist --no-progress --no-interaction laravel/laravel "$tmpdir"; then
        echo "[entrypoint] Failed to download Laravel skeleton. You can rerun inside the container with: composer create-project laravel/laravel ." >&2
        rm -rf "$tmpdir"
    else
        shopt -s dotglob
        for item in "$tmpdir"/*; do
            name="$(basename "$item")"

            # Preserve pre-existing environment template shipped with the repository
            if [ "$name" = ".env.example" ] && [ -f "${project_root}/.env.example" ]; then
                rm -rf "$item"
                continue
            fi

            if [ "$name" = ".git" ]; then
                rm -rf "$item"
                continue
            fi

            target="${project_root}/${name}"
            if [ -e "$target" ]; then
                rm -rf "$target"
            fi
            mv "$item" "$target"
        done
        shopt -u dotglob
        rmdir "$tmpdir"

        if [ ! -f "${project_root}/.env" ] && [ -f "${project_root}/.env.example" ]; then
            cp "${project_root}/.env.example" "${project_root}/.env"
        fi

        echo "[entrypoint] Laravel skeleton installed."
    fi
fi

if [ -f "${project_root}/.env" ]; then
    if grep -q '^APP_KEY=$' "${project_root}/.env"; then
        echo "[entrypoint] Generating application key..."
        (cd "$project_root" && php artisan key:generate --force)
    fi
fi

if [ ! -f "$laravel_marker" ]; then
    echo "[entrypoint] artisan is still missing. Please run 'composer create-project laravel/laravel .' manually." >&2
fi

exec docker-php-entrypoint "$@"
