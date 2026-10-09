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

use function getcwd;
use function getenv;
use function is_string;

/**
 * PHPUnit extension that measures the execution time of each test.
 *
 * At the end of the run it writes a log sorted by test duration descending
 *
 * In paratest mode each worker writes its own log under its token,
 * then all logs are merged into a shared file under a lock
 *
 * Measurement is enabled by the MEASURE_TIME environment variable (any non-empty value except 0)
 *
 * The log path is set by the log-file extension parameter or the MEASURE_TIME_LOG
 * environment variable; by default var/test-time.log relative to the current working directory is used
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
        if (!$this->isEnabled()) {
            return;
        }

        $reportWriter = new TestTimeReportWriter($this->resolveLogPath($parameters));
        $reportWriter->reset();

        $collector = new TestTimeCollector($reportWriter);

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
     * Check whether time measurement is enabled.
     *
     * The MEASURE_TIME environment variable must be non-empty and not equal to 0
     */
    private function isEnabled(): bool
    {
        $value = getenv('MEASURE_TIME');

        if (false === $value) {
            $value = $_SERVER['MEASURE_TIME'] ?? $_ENV['MEASURE_TIME'] ?? false;
        }

        return false !== $value && '' !== $value && '0' !== $value;
    }

    /**
     * Resolve the report file path.
     *
     * Priority: the log-file parameter, then the MEASURE_TIME_LOG environment variable,
     * then var/test-time.log relative to the current working directory
     *
     * @param ParameterCollection $parameters Extension parameters
     */
    private function resolveLogPath(ParameterCollection $parameters): string
    {
        if ($parameters->has('log-file')) {
            return $parameters->get('log-file');
        }

        $value = getenv('MEASURE_TIME_LOG');

        if (false === $value) {
            $value = $_SERVER['MEASURE_TIME_LOG'] ?? $_ENV['MEASURE_TIME_LOG'] ?? false;
        }

        if (is_string($value) && '' !== $value) {
            return $value;
        }

        $directory = getcwd();

        return (false === $directory ? '.' : $directory).'/var/test-time.log';
    }
}
