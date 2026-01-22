<?php

declare(strict_types=1);

namespace App;

use Closure;
use IteratorAggregate;
use Override;
use Traversable;

/**
 * Example 1: Closure parameter type inference with nested foreach destructuring
 *
 * Reproduces the CounterProviderCache pattern with actual function calls.
 */

final class LocalDate
{
    public function __construct(private string $iso = '2024-01-01') {}
    public function __toString(): string { return $this->iso; }
}

/** @implements IteratorAggregate<int, LocalDate> */
final class LocalDateInterval implements IteratorAggregate
{
    public function __construct(
        public readonly LocalDate $start,
        public readonly LocalDate $end,
    ) {}

    /** @return Traversable<int, LocalDate> */
    #[Override]
    public function getIterator(): Traversable
    {
        yield 0 => $this->start;
        yield 1 => $this->end;
    }
}

final class Context {}

final class Snowflake
{
    public function __construct(private int $value) {}
    public function value(): int { return $this->value; }
    public static function cast(int $value): self { return new self($value); }
}

/**
 * @template T
 * @param iterable<T> $items
 * @param callable(T): int $fn
 * @return array<int, list<T>>
 */
function groupBy(iterable $items, callable $fn): array
{
    $result = [];
    foreach ($items as $item) {
        $result[$fn($item)][] = $item;
    }
    return $result;
}

/**
 * @template T
 * @template U
 * @param iterable<T> $items
 * @param callable(T): U $fn
 * @return list<U>
 */
function map(iterable $items, callable $fn): array
{
    $result = [];
    foreach ($items as $item) {
        $result[] = $fn($item);
    }
    return $result;
}

/**
 * @template K of array-key
 * @template T
 */
final class CounterProviderCache
{
    /**
     * @param Closure(Context, non-empty-array<array{LocalDateInterval, array<K>}>): iterable<array{K, LocalDate}, T> $loader
     * @param Closure(Context): array<K> $potentialKeys
     */
    public function __construct(
        private Closure $loader,
        private Closure $potentialKeys,
    ) {}
}

final class AbsenceTimeRange
{
    public function __construct(private Snowflake $personId) {}
    public function personId(): Snowflake { return $this->personId; }
}

interface AbsenceTimeRangeRepositoryInterface
{
    /** @return list<AbsenceTimeRange> */
    public function find(array $resourceIds): array;
}

class BalancesApiProvider
{
    /** @var CounterProviderCache<int, list<AbsenceTimeRange>> */
    private CounterProviderCache $absencesCache;

    public function __construct(
        private AbsenceTimeRangeRepositoryInterface $absenceTimeRangeRepository,
    ) {
        /** @var Closure(Context): list<int> */
        $potentialKeys = static fn (Context $context): array => [];

        $this->absencesCache = new CounterProviderCache(
            loader: function (Context $context, array $cacheMisses): iterable {
                foreach ($cacheMisses as [$dateRange, $resourceIds]) {
                    $absences = $this->absenceTimeRangeRepository->find(
                        resourceIds: map($resourceIds, static fn (int $id): Snowflake => Snowflake::cast($id)),
                    );
                    foreach ($dateRange as $date) {
                        $absencesByResources = groupBy(
                            $absences,
                            static fn (AbsenceTimeRange $absence): int => $absence->personId()->value(),
                        );
                        foreach ($resourceIds as $resourceId) {
                            yield [$resourceId, $date] => $absencesByResources[$resourceId] ?? [];
                        }
                    }
                }
            },
            potentialKeys: $potentialKeys,
        );
    }
}
