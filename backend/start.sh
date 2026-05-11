#!/bin/bash
set -e
php artisan migrate --force
php artisan db:seed --force
frankenphp run --config /Caddyfile