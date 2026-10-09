<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests\Fixture;

use Override;
use PHPUnit\Event\Subscriber;
use PHPUnit\Event\Tracer\Tracer;
use PHPUnit\Runner\Extension\Facade;

use function array_push;

/**
 * Facade stub that remembers the registered subscribers.
 */
final class FacadeStub implements Facade
{
    /**
     * @var array<int, Subscriber>
     */
    public array $subscribers = [];

    /**
     * @param Subscriber ...$subscribers Subscribers
     */
    #[Override]
    public function registerSubscribers(Subscriber ...$subscribers): void
    {
        array_push($this->subscribers, ...$subscribers);
    }

    /**
     * @param Subscriber $subscriber Subscriber
     */
    #[Override]
    public function registerSubscriber(Subscriber $subscriber): void
    {
        $this->subscribers[] = $subscriber;
    }

    /**
     * @param Tracer $tracer Tracer
     */
    #[Override]
    public function registerTracer(Tracer $tracer): void {}

    /**
     * Replace the output.
     */
    #[Override]
    public function replaceOutput(): void {}

    /**
     * Replace the progress output.
     */
    #[Override]
    public function replaceProgressOutput(): void {}

    /**
     * Replace the result output.
     */
    #[Override]
    public function replaceResultOutput(): void {}

    /**
     * Require code coverage collection.
     */
    #[Override]
    public function requireCodeCoverageCollection(): void {}
}
