<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests\Attribute;

use AncientWeb\PhpUnitTestTime\Attribute\MaximumDuration;
use AncientWeb\PhpUnitTestTime\Exception\InvalidMaximumDuration;
use AncientWeb\PhpUnitTestTime\Tests\AbstractTestCase;

/**
 * Tests for the MaximumDuration attribute.
 *
 * @internal
 *
 * @coversNothing
 */
final class MaximumDurationTest extends AbstractTestCase
{
    /**
     * The attribute exposes its milliseconds.
     */
    public function testExposesMilliseconds(): void
    {
        $this->assertSame(1500, new MaximumDuration(1500)->milliseconds);
    }

    /**
     * A non-positive duration is rejected.
     */
    public function testRejectsNonPositiveDuration(): void
    {
        $this->expectException(InvalidMaximumDuration::class);

        new MaximumDuration(0);
    }
}
