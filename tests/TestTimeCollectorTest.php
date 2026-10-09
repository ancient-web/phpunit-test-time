<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\Tests\Fixture\TestStub;
use AncientWeb\PhpUnitTestTime\TestTimeCollector;
use AncientWeb\PhpUnitTestTime\TestTimeReportWriter;
use PHPUnit\Event\Telemetry\HRTime;

use function file_get_contents;

/**
 * Tests for the test execution time collector.
 *
 * @internal
 *
 * @coversNothing
 */
final class TestTimeCollectorTest extends AbstractTestCase
{
    /**
     * The duration is computed as the difference between start and finish.
     */
    public function testFinishComputesDurationFromStartTime(): void
    {
        $path = $this->directory.'/test-time.log';
        $collector = new TestTimeCollector(new TestTimeReportWriter($path));
        $test = new TestStub('App\Tests\DemoTest::testSomething');

        $collector->start($test, HRTime::fromSecondsAndNanoseconds(1, 0));
        $collector->finish($test, HRTime::fromSecondsAndNanoseconds(3, 500000000));
        $collector->writeReport();

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('2.5000 s', $contents);
        $this->assertStringContainsString('App\Tests\DemoTest::testSomething', $contents);
    }

    /**
     * A finish without a start is ignored.
     */
    public function testIgnoresFinishWithoutStart(): void
    {
        $path = $this->directory.'/test-time.log';
        $collector = new TestTimeCollector(new TestTimeReportWriter($path));

        $collector->finish(new TestStub('orphan-test'), HRTime::fromSecondsAndNanoseconds(5, 0));
        $collector->writeReport();

        $this->assertStringNotContainsString('orphan-test', (string) file_get_contents($path));
    }

    /**
     * The report is written only once.
     */
    public function testWritesReportOnlyOnce(): void
    {
        $path = $this->directory.'/test-time.log';
        $collector = new TestTimeCollector(new TestTimeReportWriter($path));

        $first = new TestStub('first-test');
        $collector->start($first, HRTime::fromSecondsAndNanoseconds(1, 0));
        $collector->finish($first, HRTime::fromSecondsAndNanoseconds(2, 0));
        $collector->writeReport();

        $second = new TestStub('second-test');
        $collector->start($second, HRTime::fromSecondsAndNanoseconds(1, 0));
        $collector->finish($second, HRTime::fromSecondsAndNanoseconds(2, 0));
        $collector->writeReport();

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('first-test', $contents);
        $this->assertStringNotContainsString('second-test', $contents);
    }
}
