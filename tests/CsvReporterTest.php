<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\Exception\InvalidParameter;
use AncientWeb\PhpUnitTestTime\Reporters\CsvReporter;
use AncientWeb\PhpUnitTestTime\TestTime;

/**
 * Tests for the CSV reporter.
 */
final class CsvReporterTest extends AbstractTestCase
{
    /**
     * The report has a header and numeric durations using the configured separator.
     */
    public function testWritesHeaderAndNumericDurationsWithSeparator(): void
    {
        $path = $this->directory . '/test-time.csv';

        new CsvReporter($path, null, 0, 0, '|')->report([
            'fast-test' => new TestTime(0.5),
            'slow-test' => new TestTime(2.5),
        ]);

        $contents = (string) \file_get_contents($path);

        $this->assertSame(
            ['Test case', 'Execution time (s)'],
            \str_getcsv(\explode(PHP_EOL, $contents)[0], '|', '"', '\\'),
        );
        $this->assertStringContainsString('slow-test|2.5000', $contents);
        $this->assertStringContainsString('fast-test|0.5000', $contents);
    }

    /**
     * The report is sorted by test duration descending.
     */
    public function testWriteSortsDurationsInDescendingOrder(): void
    {
        $path = $this->directory . '/test-time.csv';

        new CsvReporter($path)->report([
            'fast-test' => new TestTime(0.5),
            'slow-test' => new TestTime(2.5),
            'medium-test' => new TestTime(1.5),
        ]);

        $contents = (string) \file_get_contents($path);

        $slow = \strpos($contents, 'slow-test');
        $medium = \strpos($contents, 'medium-test');
        $fast = \strpos($contents, 'fast-test');

        $this->assertNotFalse($slow);
        $this->assertNotFalse($medium);
        $this->assertNotFalse($fast);

        $this->assertTrue($slow < $medium, 'The slowest test must come first');
        $this->assertTrue($medium < $fast, 'The fastest test must come last');
    }

    /**
     * The default separator is a semicolon.
     */
    public function testWritesWithTheDefaultSeparator(): void
    {
        $path = $this->directory . '/test-time.csv';

        new CsvReporter($path)->report([
            'some-test' => new TestTime(1.0),
        ]);

        $this->assertStringContainsString(
            'some-test;1.0000',
            (string) \file_get_contents($path),
        );
    }

    /**
     * A tab is accepted as a separator.
     */
    public function testAcceptsTabSeparator(): void
    {
        $path = $this->directory . '/test-time.csv';

        new CsvReporter($path, null, 0, 0, "\t")->report([
            'some-test' => new TestTime(1.0),
        ]);

        $this->assertStringContainsString(
            "some-test\t1.0000",
            (string) \file_get_contents($path),
        );
    }

    /**
     * A separator outside the allowed set is rejected.
     */
    public function testRejectsDisallowedSeparator(): void
    {
        $this->expectException(InvalidParameter::class);

        new CsvReporter($this->directory . '/test-time.csv', null, 0, 0, '#');
    }

    /**
     * Durations below the minimum are not written.
     */
    public function testFiltersByMinimumDuration(): void
    {
        $path = $this->directory . '/test-time.csv';

        new CsvReporter($path, null, 500)->report([
            'fast-test' => new TestTime(0.1),
            'slow-test' => new TestTime(1.0),
        ]);

        $contents = (string) \file_get_contents($path);

        $this->assertStringNotContainsString('fast-test', $contents);
        $this->assertStringContainsString('slow-test', $contents);
    }

    /**
     * Only the slowest tests are written.
     */
    public function testLimitsByMaximumCount(): void
    {
        $path = $this->directory . '/test-time.csv';

        new CsvReporter($path, null, 0, 1)->report([
            'fast-test' => new TestTime(0.1),
            'slow-test' => new TestTime(1.0),
        ]);

        $contents = (string) \file_get_contents($path);

        $this->assertStringNotContainsString('fast-test', $contents);
        $this->assertStringContainsString('slow-test', $contents);
    }

    /**
     * Merging worker reports keeps the maximum test duration.
     */
    public function testMergeKeepsMaximumDurationAcrossWorkers(): void
    {
        $path = $this->directory . '/test-time.csv';

        new CsvReporter($path, 'worker-1')->report([
            'shared-test' => new TestTime(1.0),
        ]);
        new CsvReporter($path, 'worker-2')->report([
            'shared-test' => new TestTime(3.0),
        ]);

        $contents = (string) \file_get_contents($path);

        $this->assertStringContainsString('3.0000', $contents);
        $this->assertStringNotContainsString('1.0000', $contents);
        $this->assertFileDoesNotExist($this->directory . '/test-time.worker-1.json');
        $this->assertFileDoesNotExist($this->directory . '/test-time.worker-2.json');
    }

    /**
     * The CSV reporter shares the machine log base with the log reporter, so
     * "test-time.csv" and "test-time.log" do not fight over each other's files.
     */
    public function testSharesTheJsonAccumulatorBaseWithTheLogReporter(): void
    {
        $path = $this->directory . '/test-time.csv';

        new CsvReporter($path, 'worker-1')->report([
            'shared-test' => new TestTime(1.0),
        ]);

        $this->assertFileExists($this->directory . '/test-time.json');
        $this->assertFileDoesNotExist($this->directory . '/test-time.csv.json');
    }

    /**
     * The report is created together with a missing directory.
     */
    public function testWriteCreatesMissingDirectory(): void
    {
        $path = $this->directory . '/nested/deeper/test-time.csv';

        new CsvReporter($path)->report([
            'some-test' => new TestTime(1.0),
        ]);

        $this->assertFileExists($path);
    }
}
