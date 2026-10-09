<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\Report;
use AncientWeb\PhpUnitTestTime\TestTime;
use DateTimeImmutable;

use function array_keys;
use function explode;
use function mb_strlen;
use function trim;

/**
 * Tests for preparing and rendering the report.
 */
final class ReportTest extends AbstractTestCase
{
    /**
     * Durations below the minimum are dropped.
     */
    public function testFiltersByMinimumDuration(): void
    {
        $report = Report::fromTestTimes([
            'fast' => new TestTime(0.1),
            'medium' => new TestTime(0.6),
            'slow' => new TestTime(1.2),
        ])
            ->withMinimumDuration(500)
        ;

        $this->assertSame(2, $report->count());
        $this->assertEqualsWithDelta(1.8, $report->totalSeconds(), 0.0001);
    }

    /**
     * Only the slowest tests are kept.
     */
    public function testLimitsByMaximumCount(): void
    {
        $report = Report::fromTestTimes([
            'fast' => new TestTime(0.1),
            'medium' => new TestTime(0.6),
            'slow' => new TestTime(1.2),
        ])
            ->withMaximumCount(2)
        ;

        $this->assertSame(['slow', 'medium'], array_keys($report->sortedDescending()));
    }

    /**
     * The report is rendered as a sorted table.
     */
    public function testRendersText(): void
    {
        $report = Report::fromTestTimes([
            'fast' => new TestTime(0.5),
            'slow' => new TestTime(2.5),
        ]);

        $text = $report->toText('Title', new DateTimeImmutable('2026-01-01 12:00:00'));

        $this->assertStringContainsString('Title (2026-01-01 12:00:00)', $text);
        $this->assertStringContainsString('Total tests: 2, total time: 3.0000 s', $text);
        $this->assertStringContainsString('2.5000 s  slow', $text);
        $this->assertStringContainsString('0.5000 s  fast', $text);
    }

    /**
     * A per-test minimum overrides the default for that test.
     */
    public function testPerTestMinimumOverridesDefault(): void
    {
        $report = Report::fromTestTimes([
            'allowed' => new TestTime(1.0, 2000),
            'slow' => new TestTime(1.0),
        ])
            ->withMinimumDuration(500)
        ;

        $this->assertSame(['slow'], array_keys($report->sortedDescending()));
    }

    /**
     * A zero minimum keeps everything and ignores per-test minimums.
     */
    public function testZeroMinimumIgnoresPerTestMinimum(): void
    {
        $report = Report::fromTestTimes([
            'fast' => new TestTime(0.001, 2000),
        ])
            ->withMinimumDuration(0)
        ;

        $this->assertSame(1, $report->count());
    }

    /**
     * Long identifiers are truncated in the middle to fit the width.
     */
    public function testTruncatesLongIdentifiers(): void
    {
        $id = 'AncientWeb\PhpUnitTestTime\Tests\SomeVeryLongTestClassName::testSomethingLong';

        $report = Report::fromTestTimes([$id => new TestTime(1.0)]);

        $text = $report->toText('Title', new DateTimeImmutable('2026-01-01 12:00:00'), 60);

        $this->assertStringContainsString('...', $text);

        foreach (explode(PHP_EOL, trim($text)) as $line) {
            $this->assertLessThanOrEqual(60, mb_strlen($line));
        }
    }
}
