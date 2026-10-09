<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Subscriber;

use AncientWeb\PhpUnitTestTime\TestTimeCollector;
use Override;
use PHPUnit\Event\Test\PreparationErrored;
use PHPUnit\Event\Test\PreparationErroredSubscriber;

/**
 * Records the time of a test that errored during preparation.
 */
final readonly class TestTimePreparationErroredSubscriber implements PreparationErroredSubscriber
{
    /**
     * @param TestTimeCollector $collector Test execution time collector
     */
    public function __construct(private TestTimeCollector $collector) {}

    /**
     * Handle the test preparation errored event.
     *
     * @param PreparationErrored $event PHPUnit event
     */
    #[Override]
    public function notify(PreparationErrored $event): void
    {
        $this->collector->finish($event->test()->id(), $event->telemetryInfo()->time());
    }
}
