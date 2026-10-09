<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use DateTimeImmutable;

use function array_slice;
use function array_sum;
use function count;
use function implode;
use function sprintf;
use function uasort;

/**
 * Test durations prepared for output.
 */
final readonly class Report
{
    /**
     * @param array<string, float> $durations Test durations in seconds keyed by test identifier
     */
    private function __construct(private array $durations) {}

    /**
     * Create a report from raw durations.
     *
     * @param array<string, float> $durations Test durations in seconds keyed by test identifier
     */
    public static function fromDurations(array $durations): self
    {
        return new self($durations);
    }

    /**
     * Keep only tests that took at least the given number of milliseconds.
     *
     * A non-zero minimum can be overridden per test by the resolver; a minimum of
     * zero keeps everything and ignores the resolver.
     *
     * @param int $milliseconds Minimum duration in milliseconds (0 = keep everything)
     * @param null|(callable(string): ?int) $perTestMinimum Resolves a per-test minimum in milliseconds
     */
    public function withMinimumDuration(int $milliseconds, ?callable $perTestMinimum = null): self
    {
        if (0 === $milliseconds) {
            return $this;
        }

        $durations = [];

        foreach ($this->durations as $id => $duration) {
            $minimum = null === $perTestMinimum ? $milliseconds : ($perTestMinimum($id) ?? $milliseconds);

            if ($duration >= $minimum / 1000) {
                $durations[$id] = $duration;
            }
        }

        return new self($durations);
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
     * Get the durations sorted by duration descending.
     *
     * @return array<string, float>
     */
    public function sortedDescending(): array
    {
        $durations = $this->durations;

        uasort($durations, static fn (float $first, float $second): int => $second <=> $first);

        return $durations;
    }

    /**
     * Get the number of tests.
     */
    public function count(): int
    {
        return count($this->durations);
    }

    /**
     * Get the total duration in seconds.
     */
    public function totalSeconds(): float
    {
        return array_sum($this->durations);
    }

    /**
     * Render the report as a human-readable table.
     *
     * @param string $title Report title
     * @param DateTimeImmutable $generatedAt Generation time
     */
    public function toText(string $title, DateTimeImmutable $generatedAt): string
    {
        $lines = [
            sprintf('%s (%s)', $title, $generatedAt->format('Y-m-d H:i:s')),
            sprintf('Total tests: %d, total time: %.4f s', $this->count(), $this->totalSeconds()),
            '',
        ];

        $position = 1;

        foreach ($this->sortedDescending() as $id => $duration) {
            $lines[] = sprintf('%6d. %10.4f s  %s', $position, $duration, $id);

            ++$position;
        }

        return implode(PHP_EOL, $lines).PHP_EOL;
    }
}
