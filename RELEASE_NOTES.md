## phpunit-test-time v2.2.0

A PHPUnit extension that measures the execution time of each test and, at the end of the run,
reports the slowest ones. The console report is on by default; the file log and the new CSV report
are opt-in.

This release adds a CSV report and widens the supported PHP range down to 8.1. The coding-standard
tooling moved from PHP-CS-Fixer to ECS, but the `composer cs` and `composer cs:check` scripts are
unchanged.

### Added

- An optional CSV report, controlled by the `csv`, `csv-file`, `csv-minimum-duration`,
  `csv-count`, and `csv-separator` parameters. It is off by default.

### Changed

- The minimum supported PHP version is now 8.1 (PHP 8.1 installs PHPUnit 10, 8.2 → 11, 8.3 → 12,
  and 8.4+ → 13).
- The log and CSV reports that share a name stem (as the defaults do) share the merged machine log.
- Coding standards are enforced with Easy Coding Standard (ECS) instead of PHP-CS-Fixer.

### Fixed

- The `csv-separator` parameter is now forwarded to the CSV reporter and validated.
- CSV durations are plain numbers; the unit lives in the header.

### CSV report example

```
Test case;Execution time (s)
App\Tests\SlowTest::testSomething;2.5000
App\Tests\MediumTest::testSomething;1.5000
App\Tests\FastTest::testSomething;0.5000
```

### Features

- Console report, enabled by default, showing only tests at or above `console-minimum-duration`.
- Optional file log with its own threshold and count.
- Optional CSV report with its own threshold, count, and separator.
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
