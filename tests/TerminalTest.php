<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\Terminal;

/**
 * Tests for detecting the terminal width.
 */
final class TerminalTest extends AbstractTestCase
{
    /**
     * Clear the terminal environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        putenv('COLUMNS');
    }

    /**
     * Clear the terminal environment.
     */
    protected function tearDown(): void
    {
        putenv('COLUMNS');

        parent::tearDown();
    }

    /**
     * The default width is used when the terminal width is unknown.
     */
    public function testReturnsDefaultWhenUnset(): void
    {
        $this->assertSame(80, Terminal::width());
    }

    /**
     * The COLUMNS value is used.
     */
    public function testReturnsColumns(): void
    {
        putenv('COLUMNS=120');

        $this->assertSame(120, Terminal::width());
    }

    /**
     * Narrow terminals fall back to the minimum width.
     */
    public function testReturnsMinimumForNarrowTerminals(): void
    {
        putenv('COLUMNS=40');

        $this->assertSame(80, Terminal::width());
    }
}
