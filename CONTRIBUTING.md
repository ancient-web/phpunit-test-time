# Contributing

Thanks for considering contributing to `ancient-web/phpunit-test-time`.

## Requirements

- [Docker](https://www.docker.com/) with Compose.

All commands run inside the container; the host is not expected to have PHP or Composer.

## Setup

```bash
docker compose build
```

The first `docker compose run` installs dependencies into `vendor/` via the
service entrypoint (`docker/entrypoint.sh`); later runs reconcile it with
`composer.lock` (a fast no-op). The `tests` service runs as your host user
(`${USER_ID:-1000}:${GROUP_ID:-1000}`); if your user id differs from `1000`,
copy `.env.example` to `.env` and adjust `USER_ID`/`GROUP_ID`.

## Running the quality pipeline

```bash
docker compose run --rm tests composer it
```

This runs, in order:

- `composer cs:check` — coding standards (PHP-CS-Fixer),
- `composer stan` — static analysis (PHPStan, level max),
- `composer rector:check` — automated refactoring check (Rector),
- `composer test` — the test suite.

To fix coding standards or apply refactorings:

```bash
docker compose run --rm tests composer cs
docker compose run --rm tests composer rector
```

## Pull requests

- Keep the change focused and describe the motivation.
- Update `CHANGELOG.md` under `[Unreleased]`.
- Make sure `composer it` passes.
