<?php

declare(strict_types=1);

namespace App;

/**
 * Example 2: Generic parameter not inferred in callable parameter
 *
 * Expected: No errors (Vote should be Vote<Subject> from Decision<Subject>)
 * Regression: Vote generic not resolved, generator key/value are mixed
 *
 * @see https://phpstan.org/r/fa93cc6e-6302-4468-8716-620b72437774
 */

final readonly class Subject
{
    public function id(): int
    {
        return 42;
    }
}

/**
 * @template T
 */
final readonly class Vote
{
    /**
     * @param T $subject
     */
    public function __construct(
        public bool $granted,
        public mixed $subject,
    ) {
    }
}

/**
 * @template TSubject
 */
class Decision
{
    /**
     * @param list<Vote<TSubject>> $votes
     */
    public function __construct(
        private array $votes,
    ) {
    }

    /**
     * @template U
     * @template K of array-key
     *
     * @param callable(Vote<TSubject> $vote): iterable<K, U> $fn
     *
     * @return array<K, U>
     */
    public function collect(callable $fn): array
    {
        $result = [];
        foreach ($this->votes as $vote) {
            foreach ($fn($vote) as $key => $value) {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}

class Example2
{
    public function test(): void
    {
        $decision = new Decision([new Vote(granted: true, subject: new Subject())]);
        $decision->collect(static function (Vote $vote): iterable {
            if ($vote->granted) {
                yield $vote->subject->id() => $vote->subject;
            }
        });
    }
}
