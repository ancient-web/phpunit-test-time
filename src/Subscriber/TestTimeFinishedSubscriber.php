<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Subscriber;

use AncientWeb\PhpUnitTestTime\TestTimeCollector;
use Override;
use PHPUnit\Event\Test\Finished;
use PHPUnit\Event\Test\FinishedSubscriber;

/**
 * Records the test end time and stores its duration.
 */
final class TestTimeFinishedSubscriber implements FinishedSubscriber
{
    /**
     * @param TestTimeCollector $collector Test execution time collector
     */
    public function __construct(
        private readonly TestTimeCollector $collector
    ) {
    }

    /**
     * Handle the test finished event.
     *
     * @param Finished $event PHPUnit event
     */
    #[Override]
    public function notify(Finished $event): void
    {
        $this->collector->finish($event->test()->id(), $event->telemetryInfo()->time());
    }
}
