# ancient-web/phpunit-test-time

A PHPUnit extension that measures the execution time of each test and, at the end of the run,
reports the slowest ones. The report is printed to the console by default; a file log can be
written as well.

Parallel runs via `paratest` are supported: each worker writes its own intermediate log, then all
logs are merged into a single report under an exclusive lock (for the same test the maximum
duration is kept). In parallel runs only the file log is produced — the console report is skipped,
because every worker is a separate process.

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

## Configuration

All settings are `<parameter>` elements. Durations are in milliseconds; `0` means “no limit”.

| Parameter | Type | Default | Description |
| --- | --- | --- | --- |
| `console` | bool | `true` | print the report to the console |
| `console-minimum-duration` | int | `500` | console shows only tests at or above this duration |
| `console-count` | int | `0` | maximum number of tests in the console report |
| `log` | bool | `true` | write the file log |
| `log-file` | string | `var/test-time.log` | path to the file log |
| `log-minimum-duration` | int | `0` | file log threshold (by default everything is written) |
| `log-count` | int | `0` | maximum number of tests in the file log |

For example, to show the ten slowest tests on the console, but everything in the file log:

```xml
<extensions>
    <bootstrap class="AncientWeb\PhpUnitTestTime\TestTimeExtension">
        <parameter name="console-count" value="10" />
        <parameter name="log-file" value="/tmp/test-time.log" />
    </bootstrap>
</extensions>
```

The console report respects PHPUnit's `--no-output` flag and the `stderr` configuration.

## Per-test maximum duration

A single test method can override the minimum duration with the `MaximumDuration`
attribute, or with the `@maximumDuration` / `@slowThreshold` doc-block annotations (the
latter helps migrating from `johnkary/phpunit-speedtrap`):

```php
use AncientWeb\PhpUnitTestTime\Attribute\MaximumDuration;

final class ExtraSlowTest extends TestCase
{
    #[MaximumDuration(2000)]
    public function testAllowedToBeSlow(): void
    {
    }

    /**
     * @maximumDuration 1500
     */
    public function testAlsoAllowedToBeSlow(): void
    {
    }
}
```

The override applies wherever a minimum duration is configured (the console and/or the
file log); an output configured to show everything (`0`) is not affected.

## Report format

```
Test execution time report (2026-01-01 12:00:00)
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
