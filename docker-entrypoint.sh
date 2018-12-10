#!/bin/sh
set -e

composer install --prefer-dist --no-dev --no-progress --no-suggest --optimize-autoloader --classmap-authoritative --no-interaction

exec docker-php-entrypoint "$@"