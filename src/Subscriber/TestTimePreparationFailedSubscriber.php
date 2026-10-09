<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Subscriber;

use AncientWeb\PhpUnitTestTime\TestTimeCollector;
use Override;
use PHPUnit\Event\Test\PreparationFailed;
use PHPUnit\Event\Test\PreparationFailedSubscriber;

/**
 * Records the time of a test that failed preparation.
 */
final readonly class TestTimePreparationFailedSubscriber implements PreparationFailedSubscriber
{
    /**
     * @param TestTimeCollector $collector Test execution time collector
     */
    public function __construct(private TestTimeCollector $collector) {}

    /**
     * Handle the test preparation failed event.
     *
     * @param PreparationFailed $event PHPUnit event
     */
    #[Override]
    public function notify(PreparationFailed $event): void
    {
        $this->collector->finish($event->test()->id(), $event->telemetryInfo()->time());
    }
}
