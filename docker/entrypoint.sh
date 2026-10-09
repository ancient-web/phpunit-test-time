#!/usr/bin/env sh

set -eu

# Reconcile the live-mounted vendor/ with composer.lock before running the
# command. When the lock is unchanged this is a cheap, offline no-op, so the
# container never works with stale dependencies and no volume is required.
composer install --no-interaction --no-progress

exec "$@"
