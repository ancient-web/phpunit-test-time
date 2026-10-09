<?php

declare(strict_types=1);

namespace AncientWeb\PhpUnitTestTime;

use AncientWeb\PhpUnitTestTime\Attribute\MaximumDuration;
use ReflectionMethod;

use function explode;
use function method_exists;
use function preg_match;
use function preg_split;
use function str_contains;

/**
 * Resolves the per-test maximum duration from an attribute or a doc-block annotation.
 */
final class MaximumDurationResolver
{
    /**
     * Doc-block annotations accepted for backwards compatibility.
     */
    private const array ANNOTATIONS = ['maximumDuration', 'slowThreshold'];

    /**
     * Resolve the maximum duration in milliseconds for a test identifier, or null.
     *
     * @param string $testId Test identifier (Class::method, optionally with a data set)
     */
    public static function resolve(string $testId): ?int
    {
        $method = self::methodFor($testId);

        if (!$method instanceof ReflectionMethod) {
            return null;
        }

        return self::fromAttribute($method) ?? self::fromDocBlock($method);
    }

    /**
     * Reflect the test method from a test identifier.
     *
     * @param string $testId Test identifier
     */
    private static function methodFor(string $testId): ?ReflectionMethod
    {
        if (!str_contains($testId, '::')) {
            return null;
        }

        [$class, $rest] = explode('::', $testId, 2);

        $parts = preg_split('/[ #]/', $rest, 2);
        $name = false === $parts || [] === $parts ? $rest : $parts[0];

        if (!method_exists($class, $name)) {
            return null;
        }

        return new ReflectionMethod($class, $name);
    }

    /**
     * Resolve the maximum duration from the MaximumDuration attribute.
     *
     * @param ReflectionMethod $method Test method
     */
    private static function fromAttribute(ReflectionMethod $method): ?int
    {
        $attribute = $method->getAttributes(MaximumDuration::class)[0] ?? null;

        if (null === $attribute) {
            return null;
        }

        return $attribute->newInstance()->milliseconds;
    }

    /**
     * Resolve the maximum duration from a doc-block annotation.
     *
     * @param ReflectionMethod $method Test method
     */
    private static function fromDocBlock(ReflectionMethod $method): ?int
    {
        $docComment = $method->getDocComment();

        if (false === $docComment) {
            return null;
        }

        foreach (self::ANNOTATIONS as $annotation) {
            if (1 !== preg_match('/@'.$annotation.'\s+(\d+)/', $docComment, $matches)) {
                continue;
            }

            $milliseconds = (int) $matches[1];

            return $milliseconds > 0 ? $milliseconds : null;
        }

        return null;
    }
}
