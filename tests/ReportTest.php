<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\Report;
use DateTimeImmutable;

/**
 * Tests for preparing and rendering the report.
 *
 * @internal
 *
 * @coversNothing
 */
final class ReportTest extends AbstractTestCase
{
    /**
     * Durations below the minimum are dropped.
     */
    public function testFiltersByMinimumDuration(): void
    {
        $report = Report::fromDurations(['fast' => 0.1, 'medium' => 0.6, 'slow' => 1.2])
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
        $report = Report::fromDurations(['fast' => 0.1, 'medium' => 0.6, 'slow' => 1.2])
            ->withMaximumCount(2)
        ;

        $this->assertSame(['slow' => 1.2, 'medium' => 0.6], $report->sortedDescending());
    }

    /**
     * The report is rendered as a sorted table.
     */
    public function testRendersText(): void
    {
        $report = Report::fromDurations(['fast' => 0.5, 'slow' => 2.5]);

        $text = $report->toText('Title', new DateTimeImmutable('2026-01-01 12:00:00'));

        $this->assertStringContainsString('Title (2026-01-01 12:00:00)', $text);
        $this->assertStringContainsString('Total tests: 2, total time: 3.0000 s', $text);
        $this->assertStringContainsString('2.5000 s  slow', $text);
        $this->assertStringContainsString('0.5000 s  fast', $text);
    }
}
