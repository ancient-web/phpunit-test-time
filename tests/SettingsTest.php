<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\Exception\InvalidParameter;
use AncientWeb\PhpUnitTestTime\Settings;
use PHPUnit\Runner\Extension\ParameterCollection;

/**
 * Tests for parsing the extension settings.
 *
 * @internal
 *
 * @coversNothing
 */
final class SettingsTest extends AbstractTestCase
{
    /**
     * The defaults match the documented configuration.
     */
    public function testDefaults(): void
    {
        $settings = Settings::fromParameters(ParameterCollection::fromArray([]));

        $this->assertTrue($settings->console);
        $this->assertSame(500, $settings->consoleMinimumDuration);
        $this->assertSame(0, $settings->consoleCount);
        $this->assertTrue($settings->log);
        $this->assertStringEndsWith('/var/test-time.log', $settings->logPath);
        $this->assertSame(0, $settings->logMinimumDuration);
        $this->assertSame(0, $settings->logCount);
    }

    /**
     * All parameters are read.
     */
    public function testReadsParameters(): void
    {
        $settings = Settings::fromParameters(ParameterCollection::fromArray([
            'console' => 'false',
            'console-minimum-duration' => '100',
            'console-count' => '5',
            'log' => 'false',
            'log-file' => '/tmp/test-time.log',
            'log-minimum-duration' => '250',
            'log-count' => '3',
        ]));

        $this->assertFalse($settings->console);
        $this->assertSame(100, $settings->consoleMinimumDuration);
        $this->assertSame(5, $settings->consoleCount);
        $this->assertFalse($settings->log);
        $this->assertSame('/tmp/test-time.log', $settings->logPath);
        $this->assertSame(250, $settings->logMinimumDuration);
        $this->assertSame(3, $settings->logCount);
    }

    /**
     * A non-boolean value is rejected.
     */
    public function testRejectsNonBoolean(): void
    {
        $this->expectException(InvalidParameter::class);

        Settings::fromParameters(ParameterCollection::fromArray(['console' => 'maybe']));
    }

    /**
     * A negative or non-integer value is rejected.
     */
    public function testRejectsNonInteger(): void
    {
        $this->expectException(InvalidParameter::class);

        Settings::fromParameters(ParameterCollection::fromArray(['console-count' => '-1']));
    }
}
