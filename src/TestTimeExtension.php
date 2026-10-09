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
        $environment = Environment::fromGlobals();

        if (!$environment->isMeasurementEnabled()) {
            return;
        }

        $reportWriter = new TestTimeReportWriter($this->resolveLogPath($parameters, $environment));
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
     * Resolve the report file path.
     *
     * Priority: the log-file parameter, then the MEASURE_TIME_LOG environment variable,
     * then var/test-time.log relative to the current working directory
     *
     * @param ParameterCollection $parameters Extension parameters
     * @param Environment $environment Process environment
     */
    private function resolveLogPath(ParameterCollection $parameters, Environment $environment): string
    {
        if ($parameters->has('log-file')) {
            return $parameters->get('log-file');
        }

        $path = $environment->get('MEASURE_TIME_LOG');

        if (null !== $path) {
            return $path;
        }

        $directory = getcwd();

        return (false === $directory ? '.' : $directory).'/var/test-time.log';
    }
}
