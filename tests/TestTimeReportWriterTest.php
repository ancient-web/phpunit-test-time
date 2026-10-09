<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\TestTimeReportWriter;

use function file_get_contents;
use function file_put_contents;
use function json_decode;
use function strpos;
use function time;
use function touch;

/**
 * Tests for the test execution time report writer.
 *
 * @internal
 *
 * @coversNothing
 */
final class TestTimeReportWriterTest extends AbstractTestCase
{
    /**
     * Reset worker environment variables.
     */
    protected function setUp(): void
    {
        parent::setUp();

        putenv('TEST_TOKEN');
        putenv('UNIQUE_TEST_TOKEN');
        putenv('PARATEST');
    }

    /**
     * Reset worker environment variables.
     */
    protected function tearDown(): void
    {
        putenv('TEST_TOKEN');
        putenv('UNIQUE_TEST_TOKEN');
        putenv('PARATEST');

        parent::tearDown();
    }

    /**
     * The report is sorted by test duration descending.
     */
    public function testWriteSortsDurationsInDescendingOrder(): void
    {
        $path = $this->directory.'/test-time.log';

        new TestTimeReportWriter($path)->write([
            'fast-test' => 0.5,
            'slow-test' => 2.5,
            'medium-test' => 1.5,
        ]);

        $contents = (string) file_get_contents($path);

        $slow = strpos($contents, 'slow-test');
        $medium = strpos($contents, 'medium-test');
        $fast = strpos($contents, 'fast-test');

        $this->assertNotFalse($slow);
        $this->assertNotFalse($medium);
        $this->assertNotFalse($fast);

        $this->assertTrue($slow < $medium, 'The slowest test must come first');
        $this->assertTrue($medium < $fast, 'The fastest test must come last');
        $this->assertStringContainsString('Total tests: 3', $contents);
    }

    /**
     * Merging worker reports keeps the maximum test duration.
     */
    public function testMergeKeepsMaximumDurationAcrossWorkers(): void
    {
        $path = $this->directory.'/test-time.log';

        putenv('TEST_TOKEN=worker-1');
        new TestTimeReportWriter($path)->write(['shared-test' => 1.0]);

        putenv('TEST_TOKEN=worker-2');
        new TestTimeReportWriter($path)->write(['shared-test' => 3.0]);

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('3.0000 s', $contents);
        $this->assertStringNotContainsString('1.0000 s', $contents);
        $this->assertFileDoesNotExist($this->directory.'/test-time.worker-1.json');
        $this->assertFileDoesNotExist($this->directory.'/test-time.worker-2.json');
    }

    /**
     * The merged durations are kept in a machine-readable accumulator.
     */
    public function testWritesMachineReadableAccumulator(): void
    {
        $path = $this->directory.'/test-time.log';

        putenv('TEST_TOKEN=worker-1');
        new TestTimeReportWriter($path)->write(['shared-test' => 1.0]);

        $accumulator = $this->directory.'/test-time.json';

        $this->assertFileExists($accumulator);
        $this->assertSame(
            ['shared-test' => 1.0],
            json_decode((string) file_get_contents($accumulator), true),
        );
    }

    /**
     * Reset removes reports from previous runs but leaves fresh ones untouched.
     */
    public function testResetRemovesStaleReportsAndKeepsRecentOnes(): void
    {
        $path = $this->directory.'/test-time.log';
        file_put_contents($path, 'stale');
        touch($path, time() - 3600);

        $accumulatorPath = $this->directory.'/test-time.json';
        file_put_contents($accumulatorPath, '{}');
        touch($accumulatorPath, time() - 3600);

        $workerPath = $this->directory.'/test-time.worker.json';
        file_put_contents($workerPath, '{}');
        touch($workerPath, time() - 3600);

        $freshPath = $this->directory.'/test-time.fresh.json';
        file_put_contents($freshPath, '{}');
        touch($freshPath, time() + 3600);

        new TestTimeReportWriter($path)->reset();

        $this->assertFileDoesNotExist($path);
        $this->assertFileDoesNotExist($accumulatorPath);
        $this->assertFileDoesNotExist($workerPath);
        $this->assertFileExists($freshPath);
    }

    /**
     * The report is created together with a missing directory.
     */
    public function testWriteCreatesMissingDirectory(): void
    {
        $path = $this->directory.'/nested/deeper/test-time.log';

        new TestTimeReportWriter($path)->write(['some-test' => 1.0]);

        $this->assertFileExists($path);
    }

    /**
     * An unsafe worker token does not break report merging.
     */
    public function testSanitizesUnsafeWorkerToken(): void
    {
        $path = $this->directory.'/test-time.log';

        putenv('TEST_TOKEN=weird/token:1');
        new TestTimeReportWriter($path)->write(['some-test' => 1.0]);

        $this->assertFileExists($path);
        $this->assertStringContainsString('some-test', (string) file_get_contents($path));
    }
}
