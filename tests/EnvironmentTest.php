<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\Environment;

/**
 * Tests for reading configuration from the environment.
 *
 * @internal
 *
 * @coversNothing
 */
final class EnvironmentTest extends AbstractTestCase
{
    /**
     * Name of the variable used to test environment lookups.
     */
    private const string NAME = 'PHPUNIT_TEST_TIME_ENVIRONMENT_TEST';

    /**
     * Clear the environment variables used by the tests.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearEnvironment();
    }

    /**
     * Clear the environment variables used by the tests.
     */
    protected function tearDown(): void
    {
        $this->clearEnvironment();

        parent::tearDown();
    }

    /**
     * An unset variable yields null.
     */
    public function testGetReturnsNullWhenUnset(): void
    {
        $this->assertNull(new Environment([], [])->get(self::NAME));
    }

    /**
     * A value set with putenv() is read.
     */
    public function testGetReadsValueSetWithPutenv(): void
    {
        putenv(self::NAME.'=value');

        $this->assertSame('value', new Environment([], [])->get(self::NAME));
    }

    /**
     * A value from $_SERVER is used as a fallback.
     */
    public function testGetFallsBackToServerVariables(): void
    {
        $this->assertSame('from-server', new Environment([self::NAME => 'from-server'], [])->get(self::NAME));
    }

    /**
     * A value from $_ENV is used as a fallback.
     */
    public function testGetFallsBackToEnvironmentVariables(): void
    {
        $this->assertSame('from-env', new Environment([], [self::NAME => 'from-env'])->get(self::NAME));
    }

    /**
     * An empty value yields null.
     */
    public function testGetReturnsNullForEmptyValue(): void
    {
        $this->assertNull(new Environment([self::NAME => ''], [])->get(self::NAME));
    }

    /**
     * Measurement is enabled unless MEASURE_TIME is unset, empty or 0.
     */
    public function testIsMeasurementEnabled(): void
    {
        $this->assertTrue(new Environment(['MEASURE_TIME' => '1'], [])->isMeasurementEnabled());
        $this->assertTrue(new Environment(['MEASURE_TIME' => 'yes'], [])->isMeasurementEnabled());
        $this->assertFalse(new Environment(['MEASURE_TIME' => '0'], [])->isMeasurementEnabled());
        $this->assertFalse(new Environment(['MEASURE_TIME' => ''], [])->isMeasurementEnabled());
        $this->assertFalse(new Environment([], [])->isMeasurementEnabled());
    }

    /**
     * Clear the environment variables used by the tests.
     */
    private function clearEnvironment(): void
    {
        putenv(self::NAME);
        putenv('MEASURE_TIME');

        unset(
            $_SERVER[self::NAME],
            $_ENV[self::NAME],
            $_SERVER['MEASURE_TIME'],
            $_ENV['MEASURE_TIME'],
        );
    }
}
