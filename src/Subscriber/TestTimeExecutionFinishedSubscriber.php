<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Subscriber;

use AncientWeb\PhpUnitTestTime\TestTimeCollector;
use Override;
use PHPUnit\Event\TestRunner\ExecutionFinished;
use PHPUnit\Event\TestRunner\ExecutionFinishedSubscriber;

/**
 * Writes the final test execution time report after the run finishes.
 */
final readonly class TestTimeExecutionFinishedSubscriber implements ExecutionFinishedSubscriber
{
    /**
     * @param TestTimeCollector $collector Test execution time collector
     */
    public function __construct(private TestTimeCollector $collector) {}

    /**
     * Handle the test run finished event.
     *
     * @param ExecutionFinished $event PHPUnit event
     */
    #[Override]
    public function notify(ExecutionFinished $event): void
    {
        $this->collector->writeReport();
    }
}
