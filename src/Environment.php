<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use function getenv;
use function is_string;

/**
 * Reads configuration from the process environment.
 *
 * getenv() is consulted first, then $_SERVER and $_ENV, so values set either
 * with putenv() or through the server environment are picked up
 */
final readonly class Environment
{
    /**
     * @param array<mixed> $server Server variables
     * @param array<mixed> $env Environment variables
     */
    public function __construct(
        private array $server,
        private array $env,
    ) {}

    /**
     * Create an environment from the current globals.
     */
    public static function fromGlobals(): self
    {
        return new self($_SERVER, $_ENV);
    }

    /**
     * Get the non-empty string value of an environment variable, or null.
     *
     * @param string $name Variable name
     */
    public function get(string $name): ?string
    {
        $value = getenv($name);

        if (false === $value) {
            $value = $this->server[$name] ?? $this->env[$name] ?? false;
        }

        if (!is_string($value) || '' === $value) {
            return null;
        }

        return $value;
    }

    /**
     * Whether time measurement is enabled.
     *
     * MEASURE_TIME must be set, non-empty and not equal to 0
     */
    public function isMeasurementEnabled(): bool
    {
        $value = $this->get('MEASURE_TIME');

        return null !== $value && '0' !== $value;
    }
}
