<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use AncientWeb\PhpUnitTestTime\Subscriber\TestTimeExecutionAbortedSubscriber;
use AncientWeb\PhpUnitTestTime\Subscriber\TestTimeExecutionFinishedSubscriber;
use AncientWeb\PhpUnitTestTime\Subscriber\TestTimeFinishedSubscriber;
use AncientWeb\PhpUnitTestTime\Subscriber\TestTimePreparationErroredSubscriber;
use AncientWeb\PhpUnitTestTime\Subscriber\TestTimePreparationFailedSubscriber;
use AncientWeb\PhpUnitTestTime\Subscriber\TestTimePreparationStartedSubscriber;
use Override;
use PHPUnit\Runner\Extension\Extension;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;

use function getenv;
use function getmypid;

/**
 * PHPUnit extension that measures the execution time of each test.
 *
 * At the end of the run it prints a report to the console and, optionally,
 * writes one to a file, both filtered by their own minimum duration and count
 *
 * In paratest mode the console report is skipped (each worker is a separate
 * process); the file log is merged from all workers under a lock
 *
 * All settings are configured through phpunit.xml <parameter> elements
 */
final class TestTimeExtension implements Extension
{
    /**
     * Register PHPUnit event subscribers.
     *
     * @param Configuration $configuration PHPUnit configuration
     * @param Facade $facade Facade for registering subscribers
     * @param ParameterCollection $parameters Extension parameters
     */
    #[Override]
    public function bootstrap(Configuration $configuration, Facade $facade, ParameterCollection $parameters): void
    {
        $settings = Settings::fromParameters($parameters);
        $token = $this->resolveToken();

        $reporters = [];

        if ($settings->log) {
            $reportWriter = new TestTimeReportWriter(
                $settings->logPath,
                $settings->logMinimumDuration,
                $settings->logCount,
                $token,
            );

            $reportWriter->reset();

            $reporters[] = $reportWriter;
        }

        if ($settings->console && null === $token) {
            $reporters[] = new ConsoleReporter(
                $configuration,
                $settings->consoleMinimumDuration,
                $settings->consoleCount,
                $settings->consoleMaximumWidth,
            );
        }

        if ([] === $reporters) {
            return;
        }

        $collector = new TestTimeCollector(...$reporters);

        $facade->registerSubscribers(
            new TestTimePreparationStartedSubscriber($collector),
            new TestTimeFinishedSubscriber($collector),
            new TestTimePreparationErroredSubscriber($collector),
            new TestTimePreparationFailedSubscriber($collector),
            new TestTimeExecutionFinishedSubscriber($collector),
            new TestTimeExecutionAbortedSubscriber($collector),
        );
    }

    /**
     * Resolve the current paratest worker token, or null when not in paratest.
     */
    private function resolveToken(): ?string
    {
        $token = getenv('TEST_TOKEN');

        if (false !== $token && '' !== $token) {
            return $token;
        }

        $token = getenv('UNIQUE_TEST_TOKEN');

        if (false !== $token && '' !== $token) {
            return $token;
        }

        if (false !== getenv('PARATEST')) {
            return (string) getmypid();
        }

        return null;
    }
}
