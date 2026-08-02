#!/bin/sh
set -e
php artisan migrate --force
frankenphp run --config /Caddyfile