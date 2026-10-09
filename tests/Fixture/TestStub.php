<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests\Fixture;

use PHPUnit\Event\Code\Test;

/**
 * Test stub with a given identifier used to test the time collector.
 *
 * @internal
 *
 * @coversNothing
 */
final readonly class TestStub extends Test
{
    /**
     * @param non-empty-string $testId Test identifier
     */
    public function __construct(private string $testId)
    {
        parent::__construct(__FILE__);
    }

    /**
     * Get the test identifier.
     */
    public function id(): string
    {
        return $this->testId;
    }

    /**
     * Get the test name.
     */
    public function name(): string
    {
        return $this->testId;
    }

    /**
     * Get the stable identifier used for sorting.
     */
    public function sortId(): string
    {
        return $this->testId;
    }
}
