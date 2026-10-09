<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use AncientWeb\PhpUnitTestTime\Tests\Fixture\FacadeStub;
use AncientWeb\PhpUnitTestTime\TestTimeExtension;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use ReflectionClass;

use function file_put_contents;
use function time;
use function touch;

/**
 * Tests for extension registration and report path resolution.
 *
 * @internal
 *
 * @coversNothing
 */
final class TestTimeExtensionTest extends AbstractTestCase
{
    /**
     * Clear the worker environment variables.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearEnvironment();
    }

    /**
     * Clear the worker environment variables.
     */
    protected function tearDown(): void
    {
        $this->clearEnvironment();

        parent::tearDown();
    }

    /**
     * Six subscribers are registered with the default settings.
     */
    public function testRegistersSixSubscribersByDefault(): void
    {
        $facade = new FacadeStub();

        new TestTimeExtension()->bootstrap(
            $this->configuration(),
            $facade,
            ParameterCollection::fromArray(['log-file' => $this->directory.'/test-time.log']),
        );

        $this->assertCount(6, $facade->subscribers);
    }

    /**
     * No subscribers are registered when both outputs are disabled.
     */
    public function testRegistersNoSubscribersWhenBothOutputsAreDisabled(): void
    {
        $facade = new FacadeStub();

        new TestTimeExtension()->bootstrap(
            $this->configuration(),
            $facade,
            ParameterCollection::fromArray(['console' => 'false', 'log' => 'false']),
        );

        $this->assertSame([], $facade->subscribers);
    }

    /**
     * The log-file parameter is honored and stale reports are removed.
     */
    public function testRemovesStaleReportFromConfiguredLogFile(): void
    {
        $path = $this->directory.'/custom-test-time.log';
        file_put_contents($path, 'stale');
        touch($path, time() - 3600);

        new TestTimeExtension()->bootstrap(
            $this->configuration(),
            new FacadeStub(),
            ParameterCollection::fromArray(['log-file' => $path]),
        );

        $this->assertFileDoesNotExist($path);
    }

    /**
     * Stale reports are left untouched when logging is disabled.
     */
    public function testDoesNotRemoveStaleReportWhenLoggingIsDisabled(): void
    {
        $path = $this->directory.'/test-time.log';
        file_put_contents($path, 'stale');
        touch($path, time() - 3600);

        new TestTimeExtension()->bootstrap(
            $this->configuration(),
            new FacadeStub(),
            ParameterCollection::fromArray(['console' => 'false', 'log' => 'false']),
        );

        $this->assertFileExists($path);
    }

    /**
     * Get the PHPUnit configuration without invoking the constructor.
     */
    private function configuration(): Configuration
    {
        return new ReflectionClass(Configuration::class)->newInstanceWithoutConstructor();
    }

    /**
     * Clear the worker environment variables.
     */
    private function clearEnvironment(): void
    {
        putenv('TEST_TOKEN');
        putenv('UNIQUE_TEST_TOKEN');
        putenv('PARATEST');

        unset(
            $_SERVER['TEST_TOKEN'],
            $_ENV['TEST_TOKEN'],
            $_SERVER['UNIQUE_TEST_TOKEN'],
            $_ENV['UNIQUE_TEST_TOKEN'],
            $_SERVER['PARATEST'],
            $_ENV['PARATEST'],
        );
    }
}
