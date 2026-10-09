<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Subscriber;

use AncientWeb\PhpUnitTestTime\TestTimeCollector;
use Override;
use PHPUnit\Event\TestRunner\ExecutionAborted;
use PHPUnit\Event\TestRunner\ExecutionAbortedSubscriber;

/**
 * Writes the final test execution time report when the run is aborted.
 */
final readonly class TestTimeExecutionAbortedSubscriber implements ExecutionAbortedSubscriber
{
    /**
     * @param TestTimeCollector $collector Test execution time collector
     */
    public function __construct(private TestTimeCollector $collector) {}

    /**
     * Handle the test run aborted event.
     *
     * @param ExecutionAborted $event PHPUnit event
     */
    #[Override]
    public function notify(ExecutionAborted $event): void
    {
        $this->collector->writeReport();
    }
}
