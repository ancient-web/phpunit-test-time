# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Console report (enabled by default, showing only tests at or above `console-minimum-duration`)
  in addition to the optional file log.
- Parameters `console`, `console-minimum-duration`, `console-count`, `log`, `log-minimum-duration`,
  and `log-count`.
- Per-test maximum duration via the `MaximumDuration` attribute and the `@maximumDuration` /
  `@slowThreshold` annotations.
- `console-maximum-width` parameter to truncate the console report to the terminal width.
- Machine-readable (JSON) paratest worker logs and a JSON accumulator, replacing the
  report-parsing merge.
- Static analysis with PHPStan (level max) and the strict, deprecation, and PHPUnit rules.
- Coding standards enforced with PHP-CS-Fixer.
- Automated refactoring with Rector.
- `composer` scripts for the quality pipeline.
- `CHANGELOG.md`, `CONTRIBUTING.md`, `CODE_OF_CONDUCT.md`, and `SECURITY.md`.

### Changed

- Support `phpunit/phpunit` 10 through 13 (previously only 13).
- Configure the extension exclusively through `phpunit.xml` `<parameter>` elements; the
  `MEASURE_TIME` and `MEASURE_TIME_LOG` environment variables were removed.

## [1.0.0]

### Added

- Per-test execution time measurement.
- Report sorted by duration descending, with total test count and total time.
- Opt-in via the `MEASURE_TIME` environment variable (any non-empty value except `0`).
- Configurable log path, resolved in this order:
  `log-file` extension parameter → `MEASURE_TIME_LOG` → `var/test-time.log`.
- Parallel runs via paratest: each worker writes its own intermediate log; all logs are
  merged into a single report under an exclusive lock, keeping the maximum duration per test.

[Unreleased]: https://github.com/ancient-web/phpunit-test-time/compare/1.0.0...HEAD
[1.0.0]: https://github.com/ancient-web/phpunit-test-time/releases/tag/1.0.0
