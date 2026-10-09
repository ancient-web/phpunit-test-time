<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Subscriber;

use AncientWeb\PhpUnitTestTime\TestTimeCollector;
use Override;
use PHPUnit\Event\Test\PreparationStarted;
use PHPUnit\Event\Test\PreparationStartedSubscriber;

/**
 * Records the start time of test preparation before execution.
 */
final readonly class TestTimePreparationStartedSubscriber implements PreparationStartedSubscriber
{
    /**
     * @param TestTimeCollector $collector Test execution time collector
     */
    public function __construct(private TestTimeCollector $collector) {}

    /**
     * Handle the test preparation started event.
     *
     * @param PreparationStarted $event PHPUnit event
     */
    #[Override]
    public function notify(PreparationStarted $event): void
    {
        $this->collector->start($event->test()->id(), $event->telemetryInfo()->time());
    }
}
