<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests\Fixture;

use AncientWeb\PhpUnitTestTime\Attribute\MaximumDuration;

/**
 * Fixture exposing methods with per-test maximum durations.
 */
final class MaximumDurationFixture
{
    /**
     * Method with the attribute.
     */
    #[MaximumDuration(2000)]
    public function withAttribute(): void {}

    /**
     * Method with the @maximumDuration annotation.
     *
     * @maximumDuration 3000
     */
    public function withMaximumDurationAnnotation(): void {}

    /**
     * Method with the @slowThreshold annotation.
     *
     * @slowThreshold 4000
     */
    public function withSlowThresholdAnnotation(): void {}

    /**
     * Method without an override.
     */
    public function withoutOverride(): void {}
}
