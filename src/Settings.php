<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use AncientWeb\PhpUnitTestTime\Exception\InvalidParameter;
use PHPUnit\Runner\Extension\ParameterCollection;

use function getcwd;
use function in_array;
use function preg_match;
use function strtolower;

/**
 * Extension configuration parsed from the phpunit.xml parameters.
 */
final readonly class Settings
{
    /**
     * @param bool $console Whether to print the report to the console
     * @param int $consoleMinimumDuration Console minimum duration in milliseconds
     * @param int $consoleCount Maximum number of tests in the console report (0 = unlimited)
     * @param bool $log Whether to write the file log
     * @param string $logPath Path to the file log
     * @param int $logMinimumDuration File log minimum duration in milliseconds
     * @param int $logCount Maximum number of tests in the file log (0 = unlimited)
     */
    public function __construct(
        public bool $console = true,
        public int $consoleMinimumDuration = 500,
        public int $consoleCount = 0,
        public bool $log = true,
        public string $logPath = '',
        public int $logMinimumDuration = 0,
        public int $logCount = 0,
    ) {}

    /**
     * Build settings from the extension parameters.
     *
     * @param ParameterCollection $parameters Extension parameters
     */
    public static function fromParameters(ParameterCollection $parameters): self
    {
        return new self(
            console: self::boolean($parameters, 'console', true),
            consoleMinimumDuration: self::integer($parameters, 'console-minimum-duration', 500),
            consoleCount: self::integer($parameters, 'console-count', 0),
            log: self::boolean($parameters, 'log', true),
            logPath: self::string($parameters, 'log-file', self::defaultLogPath()),
            logMinimumDuration: self::integer($parameters, 'log-minimum-duration', 0),
            logCount: self::integer($parameters, 'log-count', 0),
        );
    }

    /**
     * Read a boolean parameter.
     *
     * @param ParameterCollection $parameters Extension parameters
     * @param string $name Parameter name
     * @param bool $default Default value
     */
    private static function boolean(ParameterCollection $parameters, string $name, bool $default): bool
    {
        if (!$parameters->has($name)) {
            return $default;
        }

        $value = $parameters->get($name);

        if (in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true)) {
            return true;
        }

        if (in_array(strtolower($value), ['0', 'false', 'no', 'off'], true)) {
            return false;
        }

        throw InvalidParameter::notABoolean($name, $value);
    }

    /**
     * Read a non-negative integer parameter.
     *
     * @param ParameterCollection $parameters Extension parameters
     * @param string $name Parameter name
     * @param int $default Default value
     */
    private static function integer(ParameterCollection $parameters, string $name, int $default): int
    {
        if (!$parameters->has($name)) {
            return $default;
        }

        $value = $parameters->get($name);

        if (1 !== preg_match('/^\d+$/', $value)) {
            throw InvalidParameter::notANonNegativeInteger($name, $value);
        }

        return (int) $value;
    }

    /**
     * Read a string parameter.
     *
     * @param ParameterCollection $parameters Extension parameters
     * @param string $name Parameter name
     * @param string $default Default value
     */
    private static function string(ParameterCollection $parameters, string $name, string $default): string
    {
        if (!$parameters->has($name)) {
            return $default;
        }

        return $parameters->get($name);
    }

    /**
     * The default log path relative to the current working directory.
     */
    private static function defaultLogPath(): string
    {
        $directory = getcwd();

        return (false === $directory ? '.' : $directory).'/var/test-time.log';
    }
}
