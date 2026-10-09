<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use DateTimeImmutable;

use function array_slice;
use function count;
use function implode;
use function intdiv;
use function mb_strlen;
use function mb_substr;
use function sprintf;
use function uasort;

/**
 * Test times prepared for output.
 */
final readonly class Report
{
    /**
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    private function __construct(private array $testTimes) {}

    /**
     * Create a report from collected test times.
     *
     * @param array<string, TestTime> $testTimes Test times keyed by test identifier
     */
    public static function fromTestTimes(array $testTimes): self
    {
        return new self($testTimes);
    }

    /**
     * Keep only tests that took at least the minimum duration.
     *
     * The default minimum can be overridden per test; a minimum of zero keeps
     * everything.
     *
     * @param int $milliseconds Default minimum duration in milliseconds (0 = keep everything)
     */
    public function withMinimumDuration(int $milliseconds): self
    {
        if (0 === $milliseconds) {
            return $this;
        }

        $testTimes = [];

        foreach ($this->testTimes as $id => $testTime) {
            $minimum = $testTime->minimumMilliseconds ?? $milliseconds;

            if ($testTime->seconds >= $minimum / 1000) {
                $testTimes[$id] = $testTime;
            }
        }

        return new self($testTimes);
    }

    /**
     * Keep at most the given number of slowest tests.
     *
     * @param int $count Maximum number of tests (0 = unlimited)
     */
    public function withMaximumCount(int $count): self
    {
        if ($count <= 0) {
            return $this;
        }

        return new self(array_slice($this->sortedDescending(), 0, $count, true));
    }

    /**
     * Get the test times sorted by duration descending.
     *
     * @return array<string, TestTime>
     */
    public function sortedDescending(): array
    {
        $testTimes = $this->testTimes;

        uasort($testTimes, static fn (TestTime $first, TestTime $second): int => $second->seconds <=> $first->seconds);

        return $testTimes;
    }

    /**
     * Get the number of tests.
     */
    public function count(): int
    {
        return count($this->testTimes);
    }

    /**
     * Get the total duration in seconds.
     */
    public function totalSeconds(): float
    {
        $total = 0.0;

        foreach ($this->testTimes as $testTime) {
            $total += $testTime->seconds;
        }

        return $total;
    }

    /**
     * Render the report as a human-readable table.
     *
     * @param string $title Report title
     * @param DateTimeImmutable $generatedAt Generation time
     * @param int $maximumWidth Maximum width in columns (0 = no truncation)
     */
    public function toText(string $title, DateTimeImmutable $generatedAt, int $maximumWidth = 0): string
    {
        $lines = [
            sprintf('%s (%s)', $title, $generatedAt->format('Y-m-d H:i:s')),
            sprintf('Total tests: %d, total time: %.4f s', $this->count(), $this->totalSeconds()),
            '',
        ];

        $position = 1;

        foreach ($this->sortedDescending() as $id => $testTime) {
            $prefix = sprintf('%6d. %10.4f s  ', $position, $testTime->seconds);

            $lines[] = $prefix.$this->truncate($id, $maximumWidth - mb_strlen($prefix));

            ++$position;
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }

    /**
     * Truncate a test identifier in the middle to fit the given length.
     *
     * @param string $text Test identifier
     * @param int $maximumLength Maximum length (0 = no truncation)
     */
    private function truncate(string $text, int $maximumLength): string
    {
        if ($maximumLength <= 0 || mb_strlen($text) <= $maximumLength) {
            return $text;
        }

        if ($maximumLength <= 3) {
            return mb_substr($text, 0, $maximumLength);
        }

        $half = intdiv($maximumLength - 3, 2);
        $tailLength = $maximumLength - 3 - $half;

        return mb_substr($text, 0, $half).'...'.($tailLength > 0 ? mb_substr($text, -$tailLength) : '');
    }
}
