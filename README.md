# ancient-web/phpunit-test-time

A PHPUnit extension that measures the execution time of each test and, at the end of the run,
reports the slowest ones. The report is printed to the console by default; a file log can be
enabled as well.

Parallel runs via `paratest` are supported: each worker writes its own intermediate log, then all
logs are merged into a single report under an exclusive lock (for the same test the maximum
duration is kept). In parallel runs only the file log is produced — the console report is skipped,
because every worker is a separate process.

## Compatibility

- PHP `^8.4`
- `phpunit/phpunit` `^10.0 || ^11.0 || ^12.0 || ^13.0`

On PHPUnit 10 and 11 the `PreparationErrored` subscriber is not registered, because that event was
introduced in PHPUnit 12.

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
| `console-count` | int | `10` | maximum number of tests in the console report |
| `console-maximum-width` | int / `max` | `0` | truncate console lines to this width (`0` = no truncation, `max` = detected terminal width) |
| `log` | bool | `false` | write the file log |
| `log-file` | string | `var/test-time.log` | path to the file log |
| `log-minimum-duration` | int | `0` | file log threshold (by default everything is written) |
| `log-count` | int | `0` | maximum number of tests in the file log |

For example, to report the ten slowest tests on the console and write everything to a file log:

```xml
<extensions>
    <bootstrap class="AncientWeb\PhpUnitTestTime\TestTimeExtension">
        <parameter name="log" value="true" />
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

## Paratest

Parallel runs with [`paratest`](https://github.com/paratestphp/paratest) are supported. Because
every worker is a separate PHP process, the console report is skipped: the file log is the only
output and is merged from all workers under an exclusive lock (for a test that ran in several
workers the maximum duration is kept).

The worker token is read from `TEST_TOKEN` (falling back to `UNIQUE_TEST_TOKEN`, then the process
id when `PARATEST` is set). Point `log-file` at a shared path:

```xml
<extensions>
    <bootstrap class="AncientWeb\PhpUnitTestTime\TestTimeExtension">
        <parameter name="console" value="false" />
        <parameter name="log" value="true" />
        <parameter name="log-file" value="/tmp/test-time.log" />
        <parameter name="log-minimum-duration" value="500" />
    </bootstrap>
</extensions>
```

Each worker writes `<base>.<token>.json`, the workers merge every worker log plus the
`<base>.json` accumulator, render `<base>.log`, and remove the worker logs. A per-test
`#[MaximumDuration]` resolved by one worker is carried through the merge, so it still filters the
merged report.

## Troubleshooting

- **No console report.** The console only shows tests at or above `console-minimum-duration`
  (default `500` ms); lower it to `0` to see everything. Under paratest the console report is
  intentionally skipped, and PHPUnit's `--no-output` disables it too.
- **No file log.** Check that `log` is not `false` and that `log-file` is writable (the default is
  `getcwd()/var/test-time.log`; its directory is created automatically).
- **A test is missing.** Its duration is below the output's minimum, it was cut off by
  `console-count`/`log-count`, or a per-test `MaximumDuration` raised its own threshold. A
  per-test override only applies to outputs whose minimum duration is non-zero.
- **Invalid parameter.** `InvalidParameter` is thrown for a non-boolean `console`/`log`, a
  non-negative-integer duration/count, or a `console-maximum-width` that is neither a
  non-negative integer nor `max`.

## Report format

```
Test execution time report (2026-01-01 12:00:00)
Total tests: 3, total time: 4.5000 s

     1.     2.5000 s  App\Tests\SlowTest::testSomething
     2.     1.5000 s  App\Tests\MediumTest::testSomething
     3.     0.5000 s  App\Tests\FastTest::testSomething
```

## Comparison with other extensions

[`ergebnis/phpunit-slow-test-detector`](https://github.com/ergebnis/phpunit-slow-test-detector)
is the main inspiration for this package, and
[`johnkary/phpunit-speedtrap`](https://github.com/johnkary/phpunit-speedtrap) is the extension it
largely replaced. All three report slow tests; the differences are summarized below (as of
October 2026, based on each project's documentation).

| | phpunit-test-time | ergebnis/phpunit-slow-test-detector | johnkary/phpunit-speedtrap |
| --- | :---: | :---: | :---: |
| Console report | yes (on by default) | yes | yes |
| File report | yes (opt-in) | no | no |
| Threshold per output | yes (console and file separately) | no (one global) | no (one global) |
| Slow-test limit | yes (10) | yes (10) | yes (10) |
| Console width truncation | yes (int / `max`) | yes (int / `max`) | no |
| Per-test threshold | attribute and annotations | attribute¹ and annotations | `@slowThreshold` |
| Paratest: merged report | yes | no² | no² |
| Configuration | `phpunit.xml` parameters | `phpunit.xml` parameters | `<arguments>` |
| Supported PHPUnit | 10–13 | 6.5–13 | legacy (≤ 9) |
| Supported PHP | `^8.4` | 7.4–8.5 | not stated |
| GitHub Actions annotations | no | yes | no |

¹ On PHPUnit 10 and later, where the `MaximumDuration` attribute exists; older versions use the
`@maximumDuration` / `@slowThreshold` annotations.

² Neither extension merges reports across paratest workers: each worker prints its own console
report.

In short, this package adds a machine-readable file log that is merged across paratest workers and
preserves per-test thresholds through the merge, while `ergebnis/phpunit-slow-test-detector`
focuses on a single console table and can emit GitHub Actions annotations. Both accept the
`@slowThreshold` annotation for a smooth migration from `johnkary/phpunit-speedtrap`.

## Development

All commands run inside the container (no local PHP required):

```bash
docker compose build                       # build the image (works offline)
docker compose run --rm tests              # install dependencies and run the test suite
docker compose run --rm tests composer it  # install dependencies and run the full pipeline
docker compose run --rm tests composer coverage  # coverage (needs a pcov/xdebug driver)
docker compose run --rm tests composer audit     # dependency advisories (needs network access)
docker compose run --rm tests composer ci        # it + coverage + audit
docker compose run --rm tests composer phar      # build a self-contained PHAR
```

The quality pipeline runs coding standards (PHP-CS-Fixer), static analysis
(PHPStan, level max), automated refactoring (Rector), and the test suite. `composer it`
is offline-friendly; `composer coverage` needs a coverage driver (none is bundled), and
`composer audit`/`composer ci` need network access. See
[`CONTRIBUTING.md`](CONTRIBUTING.md) for details.

`composer phar` builds `build/phpunit-test-time.phar`, a self-contained bundle whose stub
registers an autoloader for the `AncientWeb\PhpUnitTestTime\` namespace. `require` it from your
PHPUnit bootstrap if you cannot use Composer autoloading for this package:

```php
require __DIR__.'/phpunit-test-time.phar';
```

## Credits

This project was inspired by
[`ergebnis/phpunit-slow-test-detector`](https://github.com/ergebnis/phpunit-slow-test-detector).
