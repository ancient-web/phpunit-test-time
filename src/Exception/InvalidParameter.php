<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime\Exception;

use InvalidArgumentException;

use function sprintf;

/**
 * Thrown when an extension parameter has an invalid value.
 */
final class InvalidParameter extends InvalidArgumentException
{
    /**
     * The parameter is not a boolean.
     *
     * @param string $name Parameter name
     * @param string $value Parameter value
     */
    public static function notABoolean(string $name, string $value): self
    {
        return new self(sprintf('The value "%s" of the parameter "%s" is not a boolean.', $value, $name));
    }

    /**
     * The parameter is not a non-negative integer.
     *
     * @param string $name Parameter name
     * @param string $value Parameter value
     */
    public static function notANonNegativeInteger(string $name, string $value): self
    {
        return new self(sprintf('The value "%s" of the parameter "%s" is not a non-negative integer.', $value, $name));
    }
}
