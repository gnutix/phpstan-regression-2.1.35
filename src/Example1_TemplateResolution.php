<?php

declare(strict_types=1);

namespace App;

/**
 * Example 1: Template types not resolved from generic class instantiation
 *
 * Expected: No errors (K should be resolved to int from Cache<int, string>)
 * Regression: Generator key is mixed instead of int
 *
 * @see https://phpstan.org/r/98e8f7fb-78e2-463a-859e-5ac77cea55d5
 */

/**
 * @template K of array-key
 * @template T
 */
class Cache
{
    /**
     * @param \Closure(array<K>): iterable<K, T> $loader
     * @phpstan-ignore constructor.unusedParameter
     */
    public function __construct(\Closure $loader)
    {
    }
}

class Example1
{
    /** @return Cache<int, string> */
    public function createCache(): Cache
    {
        return new Cache(
            function (array $ids): iterable {
                // $ids should be array<int>, $id should be int
                foreach ($ids as $id) {
                    yield $id => 'value';
                }
            }
        );
    }
}
