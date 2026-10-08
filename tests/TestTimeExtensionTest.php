<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests;

use ReflectionClass;
use function time;
use function touch;
use function file_put_contents;
use PHPUnit\Event\Subscriber;
use PHPUnit\Event\Tracer\Tracer;
use PHPUnit\Runner\Extension\Facade;
use PHPUnit\Runner\Extension\ParameterCollection;
use PHPUnit\TextUI\Configuration\Configuration;
use AncientWeb\PhpUnitTestTime\TestTimeExtension;

/**
 * Tests for extension registration and report path resolution
 */
final class TestTimeExtensionTest extends AbstractTestCase
{
    /**
     * Clear extension environment variables
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->clearEnvironment();
    }

    /**
     * Clear extension environment variables
     */
    protected function tearDown(): void
    {
        $this->clearEnvironment();

        parent::tearDown();
    }

    /**
     * No subscribers are registered when measurement is disabled
     */
    public function testDoesNotRegisterSubscribersWhenDisabled(): void
    {
        $facade = $this->createFacadeRecorder();

        (new TestTimeExtension())->bootstrap($this->configuration(), $facade, ParameterCollection::fromArray([]));

        $this->assertSame([], $facade->subscribers);
    }

    /**
     * The MEASURE_TIME=0 value disables measurement
     */
    public function testDoesNotRegisterSubscribersWhenDisabledWithZero(): void
    {
        putenv('MEASURE_TIME=0');

        $facade = $this->createFacadeRecorder();

        (new TestTimeExtension())->bootstrap($this->configuration(), $facade, ParameterCollection::fromArray([]));

        $this->assertSame([], $facade->subscribers);
    }

    /**
     * Six subscribers are registered when measurement is enabled
     */
    public function testRegistersSixSubscribersWhenEnabled(): void
    {
        putenv('MEASURE_TIME=1');

        $facade = $this->createFacadeRecorder();

        (new TestTimeExtension())->bootstrap(
            $this->configuration(),
            $facade,
            ParameterCollection::fromArray(['log-file' => $this->directory . '/test-time.log']),
        );

        $this->assertCount(6, $facade->subscribers);
    }

    /**
     * The report path is taken from the MEASURE_TIME_LOG environment variable
     */
    public function testReadsLogPathFromEnvironment(): void
    {
        putenv('MEASURE_TIME=1');

        $path = $this->directory . '/env-test-time.log';
        file_put_contents($path, 'stale');
        touch($path, time() - 3600);

        putenv('MEASURE_TIME_LOG=' . $path);

        (new TestTimeExtension())->bootstrap(
            $this->configuration(),
            $this->createFacadeRecorder(),
            ParameterCollection::fromArray([]),
        );

        $this->assertFileDoesNotExist($path);
    }

    /**
     * The log-file parameter takes precedence over the environment variable
     */
    public function testLogFileParameterTakesPrecedenceOverEnvironment(): void
    {
        putenv('MEASURE_TIME=1');

        $envPath = $this->directory . '/env-test-time.log';
        file_put_contents($envPath, 'stale-env');
        touch($envPath, time() - 3600);

        $parameterPath = $this->directory . '/parameter-test-time.log';
        file_put_contents($parameterPath, 'stale-parameter');
        touch($parameterPath, time() - 3600);

        putenv('MEASURE_TIME_LOG=' . $envPath);

        (new TestTimeExtension())->bootstrap(
            $this->configuration(),
            $this->createFacadeRecorder(),
            ParameterCollection::fromArray(['log-file' => $parameterPath]),
        );

        $this->assertFileDoesNotExist($parameterPath);
        $this->assertFileExists($envPath);
    }

    /**
     * Create a facade that remembers the registered subscribers
     */
    private function createFacadeRecorder(): Facade
    {
        return new class implements Facade {
            /**
             * @var array<int, Subscriber>
             */
            public array $subscribers = [];

            /**
             * @param Subscriber ...$subscribers Subscribers
             */
            public function registerSubscribers(Subscriber ...$subscribers): void
            {
                $this->subscribers = [...$this->subscribers, ...$subscribers];
            }

            /**
             * @param Subscriber $subscriber Subscriber
             */
            public function registerSubscriber(Subscriber $subscriber): void
            {
                $this->subscribers[] = $subscriber;
            }

            /**
             * @param Tracer $tracer Tracer
             */
            public function registerTracer(Tracer $tracer): void
            {
            }

            /**
             * Replace the output
             */
            public function replaceOutput(): void
            {
            }

            /**
             * Replace the progress output
             */
            public function replaceProgressOutput(): void
            {
            }

            /**
             * Replace the result output
             */
            public function replaceResultOutput(): void
            {
            }

            /**
             * Require code coverage collection
             */
            public function requireCodeCoverageCollection(): void
            {
            }
        };
    }

    /**
     * Get the PHPUnit configuration without invoking the constructor
     */
    private function configuration(): Configuration
    {
        return (new ReflectionClass(Configuration::class))->newInstanceWithoutConstructor();
    }

    /**
     * Clear extension environment variables
     */
    private function clearEnvironment(): void
    {
        putenv('MEASURE_TIME');
        putenv('MEASURE_TIME_LOG');

        unset(
            $_SERVER['MEASURE_TIME'],
            $_ENV['MEASURE_TIME'],
            $_SERVER['MEASURE_TIME_LOG'],
            $_ENV['MEASURE_TIME_LOG'],
        );
    }
}
