# AGENTS.md

PHPUnit extension (library) that records per-test execution time and writes a report sorted
by duration descending. Requires PHP `^8.4` and `phpunit/phpunit ^13.0`. This repo *is* the
extension — it is registered in the config of the projects that consume it, not in this repo's
own `phpunit.xml.dist`.

## Commands

Run the suite with Docker (image is prebuilt; this is the reliable path — see Gotchas):

```bash
docker compose run --rm tests                                              # full suite
docker compose run --rm tests vendor/bin/phpunit --filter testName         # single test
docker compose run --rm tests vendor/bin/phpunit tests/TestTimeCollectorTest.php
docker compose build                                                       # after Dockerfile/composer.json changes
```

`composer test` (runs `phpunit`) works only on a local PHP that has the `dom`, `mbstring`,
`xmlwriter` extensions; e.g. `vendor/bin/phpunit --filter testName`.

There is no linter, static analysis, typecheck, CI, or codegen configured. The test suite is the
only verification step.

## Architecture

- `src/TestTimeExtension.php` — PHPUnit `Extension`; on `bootstrap()` checks `isEnabled()`, then
  wires the collector and registers 6 event subscribers.
- `src/Subscriber/*` — thin adapters translating PHPUnit test lifecycle events
  (`PreparationStarted/Errored/Failed`, `Finished`, `ExecutionFinished/Aborted`) into
  `collector->start()/finish()/writeReport()`.
- `src/TestTimeCollector.php` — maps test id → duration; `writeReport()` writes once.
- `src/TestTimeReportWriter.php` — formats the report and handles paratest merging.

## Behavior you must not break

- **Report format is parsed back.** `TestTimeReportWriter::readFile()` re-reads the report with a
  regex (`^\s*\d+\.\s+([0-9]+\.[0-9]+) s  (.+)$`) to merge paratest worker logs. Changing the
  line format in `writeFile()` silently breaks merging — update both.
- **Paratest merging.** A worker token is resolved from `TEST_TOKEN`, then `UNIQUE_TEST_TOKEN`,
  then pid when `PARATEST` is set; otherwise no token. Each worker writes `<base>.<token>.log`,
  the shared report is rebuilt under `LOCK_EX`, durations are merged **keeping the max**, and
  worker logs are deleted. Base path strips a trailing `.log`.
- **Enable flag.** Measurement is off unless `MEASURE_TIME` is non-empty and not `0`.
- **Env lookup is dual.** `getenv()` is tried first, then `$_SERVER`/`$_ENV`. Tests set values with
  `putenv()` and must clear them in `setUp()/tearDown()` because the process is shared.
- **Report path precedence:** `log-file` extension parameter → `MEASURE_TIME_LOG` env var →
  `<getcwd()>/var/test-time.log` (the `var/` dir is gitignored).
- **`reset()` is mtime-aware.** It deletes stale report files whose mtime is older than
  `REQUEST_TIME_FLOAT` (not all files), so tests manipulate mtimes with `touch()`.

## Conventions

- Every file starts with `declare(strict_types=1);`.
- Classes are `final`; DTO-like/value classes are `readonly`.
- Every method and parameter has a docblock (the codebase style).
- Global functions are imported explicitly, e.g. `use function file_get_contents;`.
- PSR-4: `AncientWeb\PhpUnitTestTime\` → `src/`, `AncientWeb\PhpUnitTestTime\Tests\` → `tests/`.
- `composer.lock` is gitignored (unusual for a library); the Dockerfile copies `composer.lock*`
  optionally.
