<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Tests\E2E;

use AncientWeb\PhpUnitTestTime\Tests\AbstractTestCase;
use AncientWeb\PhpUnitTestTime\TestTimeExtension;
use RuntimeException;

use function array_merge;
use function dirname;
use function fclose;
use function file_put_contents;
use function getenv;
use function htmlspecialchars;
use function is_array;
use function is_dir;
use function is_resource;
use function mkdir;
use function proc_close;
use function proc_open;
use function sprintf;
use function stream_get_contents;

/**
 * Base test case that runs the extension in a separate PHPUnit process.
 *
 * The generated configuration registers the real extension and points at
 * generated test classes, so the whole event-subscriber pipeline is exercised.
 */
abstract class AbstractEndToEndTestCase extends AbstractTestCase
{
    /**
     * Run PHPUnit once with the extension registered against generated tests.
     *
     * @param array<string, string> $parameters Extension parameters
     * @param array<string, string> $sources Test file name => PHP source
     * @param array<string, string> $environment Additional environment variables
     * @param list<string> $arguments Additional command-line arguments
     *
     * @return array{exitCode: int, output: string, error: string}
     */
    protected function runExtension(
        array $parameters,
        array $sources,
        array $environment = [],
        array $arguments = [],
    ): array {
        $configPath = $this->prepareConfiguration($parameters, $sources);

        return $this->finish($this->startProcess($configPath, $environment, $arguments));
    }

    /**
     * Write the generated test sources and the PHPUnit configuration.
     *
     * @param array<string, string> $parameters Extension parameters
     * @param array<string, string> $sources Test file name => PHP source
     *
     * @return string Path to the generated configuration file
     */
    protected function prepareConfiguration(array $parameters, array $sources): string
    {
        $testsDirectory = $this->directory.'/tests';

        if (!is_dir($testsDirectory)) {
            mkdir($testsDirectory, 0o777, true);
        }

        foreach ($sources as $name => $source) {
            file_put_contents($testsDirectory.'/'.$name, $source);
        }

        $configPath = $this->directory.'/phpunit.xml';

        file_put_contents($configPath, $this->configurationXml($parameters, $testsDirectory));

        return $configPath;
    }

    /**
     * Start PHPUnit without waiting for it to finish.
     *
     * @param string $configPath Path to the configuration file
     * @param array<string, string> $environment Additional environment variables
     * @param list<string> $arguments Additional command-line arguments
     *
     * @return array{process: resource, stdout: resource, stderr: resource}
     */
    protected function startProcess(string $configPath, array $environment = [], array $arguments = []): array
    {
        $descriptors = [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open(
            $this->command($configPath, $arguments),
            $descriptors,
            $pipes,
            $this->directory,
            $this->environment($environment),
        );

        if (!is_resource($process)) {
            throw new RuntimeException('Unable to start PHPUnit');
        }

        return ['process' => $process, 'stdout' => $pipes[1], 'stderr' => $pipes[2]];
    }

    /**
     * Wait for a started PHPUnit process and collect its output.
     *
     * @param array{process: resource, stdout: resource, stderr: resource} $process Started process
     *
     * @return array{exitCode: int, output: string, error: string}
     */
    protected function finish(array $process): array
    {
        $output = (string) stream_get_contents($process['stdout']);
        $error = (string) stream_get_contents($process['stderr']);

        fclose($process['stdout']);
        fclose($process['stderr']);

        return [
            'exitCode' => proc_close($process['process']),
            'output' => $output,
            'error' => $error,
        ];
    }

    /**
     * Build the PHPUnit XML configuration registering the extension.
     *
     * @param array<string, string> $parameters Extension parameters
     * @param string $testsDirectory Directory holding the generated tests
     */
    private function configurationXml(array $parameters, string $testsDirectory): string
    {
        $parameterLines = '';

        foreach ($parameters as $name => $value) {
            $parameterLines .= sprintf(
                '      <parameter name="%s" value="%s"/>'.PHP_EOL,
                htmlspecialchars($name),
                htmlspecialchars($value),
            );
        }

        return sprintf(
            <<<'XML'
                <?xml version="1.0" encoding="UTF-8"?>
                <phpunit bootstrap="%s" cacheDirectory="%s" colors="false">
                  <testsuites>
                    <testsuite name="end-to-end">
                      <directory>%s</directory>
                    </testsuite>
                  </testsuites>
                  <extensions>
                    <bootstrap class="%s">
                %s
                    </bootstrap>
                  </extensions>
                </phpunit>
                XML,
            htmlspecialchars(dirname(__DIR__, 2).'/vendor/autoload.php'),
            htmlspecialchars($this->directory.'/.phpunit.cache'),
            htmlspecialchars($testsDirectory),
            TestTimeExtension::class,
            $parameterLines,
        );
    }

    /**
     * Build the PHPUnit command line.
     *
     * @param string $configPath Path to the configuration file
     * @param list<string> $arguments Additional command-line arguments
     *
     * @return list<string>
     */
    private function command(string $configPath, array $arguments): array
    {
        return [
            PHP_BINARY,
            dirname(__DIR__, 2).'/vendor/bin/phpunit',
            '--configuration',
            $configPath,
            '--colors=never',
            ...$arguments,
        ];
    }

    /**
     * Build the environment for the child process.
     *
     * Inherited paratest variables are removed so each run starts clean.
     *
     * @param array<string, string> $environment Additional environment variables
     *
     * @return array<string, string>
     */
    private function environment(array $environment): array
    {
        $inherited = getenv();

        if (!is_array($inherited)) {
            $inherited = [];
        }

        unset($inherited['TEST_TOKEN'], $inherited['UNIQUE_TEST_TOKEN'], $inherited['PARATEST']);

        $inherited['COLUMNS'] = '80';

        return array_merge($inherited, $environment);
    }
}
