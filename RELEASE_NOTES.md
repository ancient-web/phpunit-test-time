## phpunit-test-time v2.1.0

A PHPUnit extension that measures the execution time of each test and, at the end of the run,
reports the slowest ones. The console report is on by default; the file log is opt-in.

This release adjusts the default behavior and documents how the extension compares to the
alternatives.

### Changed

- The console report shows at most the 10 slowest tests by default (`console-count` = `10`).
- The file log is disabled by default; enable it with the `log` parameter.
- The console report stays enabled by default with `console-minimum-duration` = `500` ms.

### Added

- A [comparison with other extensions](https://github.com/ancient-web/phpunit-test-time#comparison-with-other-extensions)
  (`ergebnis/phpunit-slow-test-detector` and `johnkary/phpunit-speedtrap`) in the README.

### Features

- Console report, enabled by default, showing only tests at or above `console-minimum-duration`.
- Optional file log with its own threshold and count.
- `console-maximum-width` to truncate the console report to the terminal width (or `max`).
- Per-test maximum duration via the `MaximumDuration` attribute and the `@maximumDuration` /
  `@slowThreshold` annotations; the per-test minimum survives the paratest merge.
- Parallel runs via paratest: each worker writes a machine-readable (JSON) log; all worker logs
  and the accumulator are merged under an exclusive lock, keeping the maximum duration per test.

### Report example

```
Test execution time report (2026-01-01 12:00:00)
Total tests: 3, total time: 4.5000 s

     1.     2.5000 s  App\Tests\SlowTest::testSomething
     2.     1.5000 s  App\Tests\MediumTest::testSomething
     3.     0.5000 s  App\Tests\FastTest::testSomething
```

### Requirements

- PHP `^8.4`
- PHPUnit `^10.0 || ^11.0 || ^12.0 || ^13.0`
- PHP extension: `mbstring` (PHPUnit pulls in `dom` and `xmlwriter`)

### Installation

```bash
composer require --dev ancient-web/phpunit-test-time
```

### Registration

```xml
<extensions>
    <bootstrap class="AncientWeb\PhpUnitTestTime\TestTimeExtension">
        <parameter name="log" value="true" />
        <parameter name="log-file" value="/tmp/test-time.log" />
    </bootstrap>
</extensions>
```

### Defaults

| Parameter | Default |
| --- | --- |
| `console` | `true` |
| `console-minimum-duration` | `500` |
| `console-count` | `10` |
| `console-maximum-width` | `0` |
| `log` | `false` |
| `log-file` | `var/test-time.log` |
| `log-minimum-duration` | `0` |
| `log-count` | `0` |

### License

MIT
