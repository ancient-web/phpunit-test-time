<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests\E2E;

use function array_merge;
use function explode;
use function file_get_contents;
use function mb_strlen;
use function str_contains;

/**
 * End-to-end tests that run the real extension in a separate PHPUnit process.
 */
final class ExtensionEndToEndTest extends AbstractEndToEndTestCase
{
    /**
     * Only tests at or above the console minimum duration are reported.
     */
    public function testPrintsOnlyTestsAboveTheConsoleMinimumDuration(): void
    {
        $result = $this->runExtension(
            $this->consoleOnly(['console-minimum-duration' => '500']),
            ['SlowAndFastTest.php' => $this->slowAndFastTest()],
        );

        $this->assertSame(0, $result['exitCode'], $result['error']);
        $this->assertStringContainsString('Test execution time report', $result['output']);
        $this->assertStringContainsString('testSlowTest', $result['output']);
        $this->assertStringNotContainsString('testFastTest', $result['output']);
    }

    /**
     * The console report keeps at most the configured number of slowest tests.
     */
    public function testConsoleReportLimitsTheNumberOfTests(): void
    {
        $result = $this->runExtension(
            $this->consoleOnly(['console-minimum-duration' => '0', 'console-count' => '1']),
            ['OrderingTest.php' => $this->orderingTest()],
        );

        $this->assertSame(0, $result['exitCode'], $result['error']);
        $this->assertStringContainsString('testSlowest', $result['output']);
        $this->assertStringNotContainsString('testMedium', $result['output']);
        $this->assertStringNotContainsString('testFastest', $result['output']);
    }

    /**
     * Long identifiers are truncated to the configured console width.
     */
    public function testConsoleReportTruncatesLongIdentifiers(): void
    {
        $result = $this->runExtension(
            $this->consoleOnly(['console-minimum-duration' => '0', 'console-maximum-width' => '40']),
            ['LongIdentifierTest.php' => $this->longIdentifierTest()],
        );

        $this->assertSame(0, $result['exitCode'], $result['error']);

        $line = $this->lineContaining($result['output'], 'Ancient');

        $this->assertNotSame('', $line);
        $this->assertLessThanOrEqual(40, mb_strlen($line));
        $this->assertStringContainsString('...', $line);
        $this->assertStringNotContainsString('testMethodWithAnExtremelyLongName', $line);
    }

    /**
     * The console report is skipped when PHPUnit output is disabled.
     */
    public function testConsoleReportIsSkippedWhenOutputIsDisabled(): void
    {
        $result = $this->runExtension(
            $this->consoleOnly(),
            ['SlowAndFastTest.php' => $this->slowAndFastTest()],
            [],
            ['--no-output'],
        );

        $this->assertSame(0, $result['exitCode'], $result['error']);
        $this->assertStringNotContainsString('Test execution time report', $result['output']);
    }

    /**
     * The console report follows PHPUnit to the standard error stream.
     */
    public function testConsoleReportIsWrittenToTheStandardErrorStream(): void
    {
        $result = $this->runExtension(
            $this->consoleOnly(),
            ['SlowAndFastTest.php' => $this->slowAndFastTest()],
            [],
            ['--stderr'],
        );

        $this->assertSame(0, $result['exitCode'], $result['error']);
        $this->assertStringNotContainsString('Test execution time report', $result['output']);
        $this->assertStringContainsString('Test execution time report', $result['error']);
    }

    /**
     * The file report is written and filtered by the log minimum duration.
     */
    public function testWritesTheFileReport(): void
    {
        $path = $this->directory.'/test-time.log';

        $result = $this->runExtension(
            [
                'console' => 'false',
                'log' => 'true',
                'log-file' => $path,
                'log-minimum-duration' => '500',
            ],
            ['SlowAndFastTest.php' => $this->slowAndFastTest()],
        );

        $this->assertSame(0, $result['exitCode'], $result['error']);
        $this->assertFileExists($path);

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('testSlowTest', $contents);
        $this->assertStringNotContainsString('testFastTest', $contents);
    }

    /**
     * A per-test maximum duration overrides the console minimum duration.
     */
    public function testPerTestMaximumDurationOverridesTheConsoleMinimum(): void
    {
        $result = $this->runExtension(
            $this->consoleOnly(['console-minimum-duration' => '100']),
            ['OverrideTest.php' => $this->overrideTest()],
        );

        $this->assertSame(0, $result['exitCode'], $result['error']);
        $this->assertStringContainsString('testShortButAllowed', $result['output']);
        $this->assertStringNotContainsString('testSlowButSuppressed', $result['output']);
    }

    /**
     * Concurrent worker runs are merged into a single file report.
     */
    public function testMergesParatestWorkerReports(): void
    {
        $path = $this->directory.'/test-time.log';

        $configPath = $this->prepareConfiguration(
            ['console' => 'true', 'log' => 'true', 'log-file' => $path],
            ['TokenTest.php' => $this->tokenTest()],
        );

        $firstProcess = $this->startProcess($configPath, ['TEST_TOKEN' => '1']);
        $secondProcess = $this->startProcess($configPath, ['TEST_TOKEN' => '2']);

        $first = $this->finish($firstProcess);
        $second = $this->finish($secondProcess);

        $this->assertSame(0, $first['exitCode'], $first['error']);
        $this->assertSame(0, $second['exitCode'], $second['error']);

        $this->assertFileExists($path);

        $contents = (string) file_get_contents($path);

        $this->assertStringContainsString('testShared', $contents);
        $this->assertStringContainsString('0.5', $contents);
        $this->assertStringNotContainsString('0.1', $contents);

        $this->assertFileExists($this->directory.'/test-time.json');
        $this->assertFileDoesNotExist($this->directory.'/test-time.1.json');
        $this->assertFileDoesNotExist($this->directory.'/test-time.2.json');

        // The console report is skipped while running in paratest.
        $this->assertStringNotContainsString('Test execution time report', $first['output']);
        $this->assertStringNotContainsString('Test execution time report', $second['output']);
    }

    /**
     * The default console-only parameters.
     *
     * @param array<string, string> $parameters Overrides
     *
     * @return array<string, string>
     */
    private function consoleOnly(array $parameters = []): array
    {
        return array_merge(['console' => 'true', 'log' => 'false'], $parameters);
    }

    /**
     * Get the first line containing the given text.
     *
     * @param string $text Text to search
     * @param string $needle Needle to find
     */
    private function lineContaining(string $text, string $needle): string
    {
        foreach (explode(PHP_EOL, $text) as $line) {
            if (str_contains($line, $needle)) {
                return $line;
            }
        }

        return '';
    }

    /**
     * A test class with one slow test and one fast test.
     */
    private function slowAndFastTest(): string
    {
        return <<<'SOURCE'
            <?php

            declare(strict_types=1);

            namespace AncientWeb\PhpUnitTestTime\Tests\E2E\Fixture;

            use PHPUnit\Framework\TestCase;

            final class SlowAndFastTest extends TestCase
            {
                public function testSlowTest(): void
                {
                    usleep(600000);

                    $this->assertTrue(true);
                }

                public function testFastTest(): void
                {
                    $this->assertTrue(true);
                }
            }
            SOURCE;
    }

    /**
     * A test class with three clearly ordered test durations.
     */
    private function orderingTest(): string
    {
        return <<<'SOURCE'
            <?php

            declare(strict_types=1);

            namespace AncientWeb\PhpUnitTestTime\Tests\E2E\Fixture;

            use PHPUnit\Framework\TestCase;

            final class OrderingTest extends TestCase
            {
                public function testSlowest(): void
                {
                    usleep(600000);

                    $this->assertTrue(true);
                }

                public function testMedium(): void
                {
                    usleep(400000);

                    $this->assertTrue(true);
                }

                public function testFastest(): void
                {
                    $this->assertTrue(true);
                }
            }
            SOURCE;
    }

    /**
     * A test class with a very long test identifier.
     */
    private function longIdentifierTest(): string
    {
        return <<<'SOURCE'
            <?php

            declare(strict_types=1);

            namespace AncientWeb\PhpUnitTestTime\Tests\E2E\Fixture;

            use PHPUnit\Framework\TestCase;

            final class LongIdentifierTest extends TestCase
            {
                public function testMethodWithAnExtremelyLongName(): void
                {
                    $this->assertTrue(true);
                }
            }
            SOURCE;
    }

    /**
     * A test class with per-test maximum durations in both directions.
     */
    private function overrideTest(): string
    {
        return <<<'SOURCE'
            <?php

            declare(strict_types=1);

            namespace AncientWeb\PhpUnitTestTime\Tests\E2E\Fixture;

            use AncientWeb\PhpUnitTestTime\Attribute\MaximumDuration;
            use PHPUnit\Framework\TestCase;

            final class OverrideTest extends TestCase
            {
                #[MaximumDuration(1)]
                public function testShortButAllowed(): void
                {
                    usleep(5000);

                    $this->assertTrue(true);
                }

                #[MaximumDuration(1000000)]
                public function testSlowButSuppressed(): void
                {
                    usleep(200000);

                    $this->assertTrue(true);
                }
            }
            SOURCE;
    }

    /**
     * A test class whose duration depends on the paratest worker token.
     */
    private function tokenTest(): string
    {
        return <<<'SOURCE'
            <?php

            declare(strict_types=1);

            namespace AncientWeb\PhpUnitTestTime\Tests\E2E\Fixture;

            use PHPUnit\Framework\TestCase;

            final class TokenTest extends TestCase
            {
                public function testShared(): void
                {
                    usleep(1 === (int) getenv('TEST_TOKEN') ? 100000 : 500000);

                    $this->assertTrue(true);
                }
            }
            SOURCE;
    }
}
