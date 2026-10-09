# ancient-web/phpunit-test-time

A PHPUnit extension that measures the execution time of each test and, at the end of the run,
writes a report sorted by duration descending.

Parallel runs via `paratest` are supported: each worker writes its own intermediate log, then all
logs are merged into a single report under an exclusive lock (for the same test the maximum
duration is kept).

## Installation

```bash
composer require --dev ancient-web/phpunit-test-time
```

## Registration

Register the extension in `phpunit.xml.dist`:

```xml
<extensions>
    <bootstrap class="AncientWeb\PhpUnitTestTime\TestTimeExtension" />
</extensions>
```

## Enabling measurement

Measurement is disabled by default. Enable it with the `MEASURE_TIME` environment variable — any
non-empty value except `0`:

```bash
MEASURE_TIME=1 vendor/bin/phpunit
```

## Log path

The path is resolved in the following order of priority:

1. The `log-file` extension parameter:

   ```xml
   <bootstrap class="AncientWeb\PhpUnitTestTime\TestTimeExtension">
       <parameter name="log-file" value="/tmp/test-time.log" />
   </bootstrap>
   ```

2. The `MEASURE_TIME_LOG` environment variable:

   ```bash
   MEASURE_TIME=1 MEASURE_TIME_LOG=/tmp/test-time.log vendor/bin/phpunit
   ```

3. The default — `var/test-time.log` relative to the current working directory.

## Report format

```
Final test execution time report (2026-01-01 12:00:00)
Total tests: 3, total time: 4.5000 s

     1.     2.5000 s  App\Tests\SlowTest::testSomething
     2.     1.5000 s  App\Tests\MediumTest::testSomething
     3.     0.5000 s  App\Tests\FastTest::testSomething
```

## Development

All commands run inside the container (no local PHP required):

```bash
docker compose build                       # build the image (works offline)
docker compose run --rm tests              # install dependencies and run the test suite
docker compose run --rm tests composer it  # install dependencies and run the full pipeline
```

The quality pipeline runs coding standards (PHP-CS-Fixer), static analysis
(PHPStan, level max), automated refactoring (Rector), and the test suite. See
[`CONTRIBUTING.md`](CONTRIBUTING.md) for details.
