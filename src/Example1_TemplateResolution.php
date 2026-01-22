<?php

declare(strict_types=1);

namespace App;

/**
 * Example 1: Closure parameter type inference with nested foreach destructuring
 *
 * The closure's second parameter should be inferred as
 * non-empty-array<array{LocalDateInterval, array<int>}> from the expected type,
 * but it's inferred as just "array", causing destructuring to produce "mixed".
 */

class Context {}

class LocalDate {}

/** @implements \IteratorAggregate<int, LocalDate> */
class LocalDateInterval implements \IteratorAggregate
{
    /** @return \Traversable<int, LocalDate> */
    public function getIterator(): \Traversable
    {
        yield 0 => new LocalDate();
    }
}

/**
 * @template K of array-key
 * @template T
 */
final class Cache
{
    /**
     * @param \Closure(Context, non-empty-array<array{LocalDateInterval, array<K>}>): iterable<array{K, LocalDate}, T> $loader
     */
    public function __construct(\Closure $loader)
    {
    }
}

/**
 * @return Cache<int, list<string>>
 */
function createCache(): Cache
{
    return new Cache(
        function (Context $context, array $cacheMisses): iterable {
            foreach ($cacheMisses as [$dateRange, $resourceIds]) {
                foreach ($dateRange as $date) {
                    foreach ($resourceIds as $resourceId) {
                        yield [$resourceId, $date] => [];
                    }
                }
            }
        }
    );
}
