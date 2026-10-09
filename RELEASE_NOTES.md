## phpunit-test-time v2.0.0

A PHPUnit extension that measures the execution time of each test and, at the end of the run,
reports the slowest ones. The console report is on by default; a file log can be written as well.

This is a major release: configuration is now parameter-only (the `MEASURE_TIME` /
`MEASURE_TIME_LOG` environment variables were removed), and the supported range widens to
PHPUnit 10 through 13.

### Features

- Console report, enabled by default, showing only tests at or above `console-minimum-duration`.
- Optional file log with its own threshold and count.
- `console-maximum-width` to truncate the console report to the terminal width (or `max`).
- Per-test maximum duration via the `MaximumDuration` attribute and the `@maximumDuration` /
  `@slowThreshold` annotations; the per-test minimum survives the paratest merge.
- Parallel runs via paratest: each worker writes a machine-readable (JSON) log; all worker logs
  and the accumulator are merged under an exclusive lock, keeping the maximum duration per test.
- End-to-end tests that run the real extension in a separate PHPUnit process.

### Breaking changes

- Configuration is exclusively through `phpunit.xml` `<parameter>` elements.
- The `MEASURE_TIME` and `MEASURE_TIME_LOG` environment variables were removed.
- Registering the extension is what enables it.

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
        <parameter name="console-count" value="10" />
        <parameter name="log-file" value="/tmp/test-time.log" />
    </bootstrap>
</extensions>
```

### License

MIT
