## phpunit-test-time v1.0.0

First release of a PHPUnit extension that measures the execution time of each test and,
at the end of the run, writes a report sorted by duration descending.

### Features

- Per-test execution time measurement.
- Report sorted by duration descending, with total test count and total time.
- Opt-in via the `MEASURE_TIME` environment variable (any non-empty value except `0`).
- Configurable log path, resolved in this order:
  `log-file` extension parameter → `MEASURE_TIME_LOG` → `var/test-time.log`.
- Parallel runs via paratest: each worker writes its own intermediate log; all logs are
  merged into a single report under an exclusive lock, keeping the maximum duration per test.

### Report example

```
Final test execution time report (2026-01-01 12:00:00)
Total tests: 3, total time: 4.5000 s

     1.     2.5000 s  App\Tests\SlowTest::testSomething
     2.     1.5000 s  App\Tests\MediumTest::testSomething
     3.     0.5000 s  App\Tests\FastTest::testSomething
```

### Requirements

- PHP `^8.4`
- PHPUnit `^13.0`
- PHP extensions: `dom`, `mbstring`, `xmlwriter`

### Installation

```bash
composer require --dev ancient-web/phpunit-test-time
```

### Registration

```xml
<extensions>
    <bootstrap class="AncientWeb\PhpUnitTestTime\TestTimeExtension" />
</extensions>
```

### Enable measurement

```bash
MEASURE_TIME=1 vendor/bin/phpunit
```

### License

MIT
