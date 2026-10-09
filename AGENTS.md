# AGENTS.md

PHPUnit extension (library) that records per-test execution time and writes a report sorted
by duration descending. Requires PHP `^8.4` and `phpunit/phpunit ^13.0`. This repo *is* the
extension — it is registered in the config of the projects that consume it, not in this repo's
own `phpunit.xml.dist`.

## Commands

> **Run every command inside the container.** Do not run `php`, `composer`, `vendor/bin/*`,
> or any other tooling directly on the host — the host PHP lacks the required extensions.
> Use `docker compose run --rm tests <command>` (or `docker compose build`).
> The `tests` service live-mounts the repo and runs an entrypoint (`docker/entrypoint.sh`)
> that reconciles `vendor/` with `composer.lock` on every `run` (a cheap, offline no-op
> when unchanged), so dependencies live in the working copy and no volume is needed. It
> runs as `${USER_ID:-1000}:${GROUP_ID:-1000}` so files written to the working copy are
> owned by you, not root; override via `.env` (see `.env.example`).

Run the suite with Docker (the entrypoint installs dependencies on the first run):

```bash
docker compose run --rm tests                                              # full suite
docker compose run --rm tests vendor/bin/phpunit --filter testName         # single test
docker compose run --rm tests vendor/bin/phpunit tests/TestTimeCollectorTest.php
docker compose build                                                       # only after Dockerfile changes
```

Quality tooling (all inside the container, via the `composer` scripts):

```bash
docker compose run --rm tests composer it          # cs:check + stan + rector:check + test
docker compose run --rm tests composer cs          # fix coding standards (PHP-CS-Fixer)
docker compose run --rm tests composer cs:check    # check coding standards only
docker compose run --rm tests composer stan        # static analysis (PHPStan, level max)
docker compose run --rm tests composer rector      # automated refactoring (Rector)
```

## Architecture

- `src/TestTimeExtension.php` — PHPUnit `Extension`; on `bootstrap()` parses `Settings`, resolves
  the paratest token, wires the reporters, and registers 6 event subscribers.
- `src/Settings.php` — parses the `phpunit.xml` `<parameter>` elements into typed settings.
- `src/Report.php` — prepares durations for output (minimum duration, maximum count, sorting,
  rendering the human table).
- `src/Reporter.php` + `src/ConsoleReporter.php` + `src/TestTimeReportWriter.php` — the console
  reporter and the (merging) file reporter.
- `src/Subscriber/*` — thin adapters translating PHPUnit test lifecycle events
  (`PreparationStarted/Errored/Failed`, `Finished`, `ExecutionFinished/Aborted`) into
  `collector->start()/finish()/writeReport()`.
- `src/TestTimeCollector.php` — maps test id → duration; `writeReport()` calls each reporter once.
- `src/Exception/*` — `InvalidParameter` and `ReportWriteFailed`.

## Behavior you must not break

- **Two formats.** Worker logs and the shared accumulator are JSON (machine-readable); the human
  report (`<base>.log`) is rendered from the accumulator. Merging never parses the human report,
  so its line format can change freely.
- **Paratest merging.** A worker token is resolved from `TEST_TOKEN`, then `UNIQUE_TEST_TOKEN`,
  then pid when `PARATEST` is set; otherwise there is no token and the human report is written
  directly. With a token, the worker writes `<base>.<token>.json`, then merges every
  `<base>.*.json` worker log plus the `<base>.json` accumulator under `LOCK_EX`, keeping the
  **maximum** duration per test, writes the accumulator and `<base>.log`, and deletes the worker
  logs. Base path strips a trailing `.log`.
- **Configuration is parameter-only.** All settings come from `phpunit.xml` `<parameter>` elements
  (`Settings::fromParameters`); there is no enable flag and no configuration environment variable.
  Registering the extension is what enables it.
- **Console vs file.** The console report is printed only when not in paratest (every worker is a
  separate process) and respects PHPUnit's `noOutput()`/`outputToStandardErrorStream()`. The file
  log is the only paratest output and merges all workers.
- **Env is only for paratest.** `getenv()` is used solely to resolve the worker token
  (`TEST_TOKEN`, then `UNIQUE_TEST_TOKEN`, then pid when `PARATEST` is set); tests clear these in
  `setUp()/tearDown()` because the process is shared.
- **Default log path:** the `log-file` parameter, otherwise `<getcwd()>/var/test-time.log` (the
  `var/` dir is gitignored).
- **Per-test overrides.** `MaximumDurationResolver::resolve()` reads a test's `#[MaximumDuration]`
  attribute (then the `@maximumDuration`, then the `@slowThreshold` annotation) from the
  `Class::method` part of its identifier, so the override survives the paratest merge. It only
  affects outputs whose minimum duration is non-zero.
- **`reset()` is mtime-aware.** It deletes stale report files whose mtime is older than
  `REQUEST_TIME_FLOAT` (not all files), so tests manipulate mtimes with `touch()`.

## Conventions

- Every file starts with `declare(strict_types=1);`.
- Classes are `final`; DTO-like/value classes are `readonly`.
- Every method and parameter has a docblock (the codebase style).
- Global functions are imported explicitly, e.g. `use function file_get_contents;`.
- PSR-4: `AncientWeb\PhpUnitTestTime\` → `src/`, `AncientWeb\PhpUnitTestTime\Tests\` → `tests/`.
- `composer.lock` is gitignored (library convention); the Docker entrypoint installs dependencies
  from it into the live-mounted `vendor/` on each run.
