#!/usr/bin/env sh
set -e

cd /opt/www

if [ ! -f composer.json ]; then
  echo "composer.json was not found in /opt/www."
  echo "Create or mount a Hyperf project first, for example:"
  echo "  docker compose run --rm --entrypoint composer app create-project hyperf/hyperf-skeleton ."
  exit 1
fi

if [ ! -d vendor ]; then
  composer install --no-interaction --prefer-dist --optimize-autoloader
fi

exec "$@"
