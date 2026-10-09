<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\MaximumDurationResolver;
use AncientWeb\PhpUnitTestTime\Tests\Fixture\MaximumDurationFixture;

/**
 * Tests for resolving the per-test maximum duration.
 *
 * @internal
 *
 * @coversNothing
 */
final class MaximumDurationResolverTest extends AbstractTestCase
{
    /**
     * The attribute is resolved.
     */
    public function testResolvesAttribute(): void
    {
        $this->assertSame(2000, MaximumDurationResolver::resolve(MaximumDurationFixture::class.'::withAttribute'));
    }

    /**
     * The @maximumDuration annotation is resolved.
     */
    public function testResolvesMaximumDurationAnnotation(): void
    {
        $this->assertSame(
            3000,
            MaximumDurationResolver::resolve(MaximumDurationFixture::class.'::withMaximumDurationAnnotation'),
        );
    }

    /**
     * The @slowThreshold annotation is resolved.
     */
    public function testResolvesSlowThresholdAnnotation(): void
    {
        $this->assertSame(
            4000,
            MaximumDurationResolver::resolve(MaximumDurationFixture::class.'::withSlowThresholdAnnotation'),
        );
    }

    /**
     * A method without an override yields null.
     */
    public function testReturnsNullWithoutOverride(): void
    {
        $this->assertNull(MaximumDurationResolver::resolve(MaximumDurationFixture::class.'::withoutOverride'));
    }

    /**
     * A data set suffix is ignored.
     */
    public function testIgnoresDataSetSuffix(): void
    {
        $this->assertSame(2000, MaximumDurationResolver::resolve(MaximumDurationFixture::class.'::withAttribute#0'));
        $this->assertSame(
            2000,
            MaximumDurationResolver::resolve(MaximumDurationFixture::class.'::withAttribute with data set #0'),
        );
    }

    /**
     * A test identifier without a method yields null.
     */
    public function testReturnsNullWithoutMethod(): void
    {
        $this->assertNull(MaximumDurationResolver::resolve('closure-test'));
        $this->assertNull(MaximumDurationResolver::resolve('UnknownClass::unknownMethod'));
    }
}
