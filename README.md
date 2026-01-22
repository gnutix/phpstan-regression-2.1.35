# PHPStan 2.1.35 Regression Test

This repository contains minimal reproduction cases for a regression introduced in PHPStan 2.1.35.

## Issue

https://github.com/phpstan/phpstan/issues/13993

## The Regression

Closure parameter types are no longer inferred from the expected `Closure` type signature when passed to a function/constructor.

### Results

| Version | Example 1 | Example 2 |
|---------|-----------|-----------|
| 2.1.33 | ✅ No errors | ✅ No errors |
| 2.1.34 | ✅ No errors | ✅ No errors |
| 2.1.35 | ❌ Regression | ❌ Regression |
| 2.1.x-dev | ❌ Regression | ❌ Regression |

## Example 1: CounterProviderCache Pattern

A generic class with a `Closure` parameter that has typed array parameters. The closure's second parameter should be inferred as `non-empty-array<array{LocalDateInterval, array<int>}>` but is inferred as just `array`, causing:
- Array destructuring to produce `mixed` types
- Generator key to become `array{mixed, mixed}` instead of `array{int, LocalDate}`

## Example 2: Decision::collect Pattern

A generic class method accepting a callable with a generic parameter. The callable's parameter should be inferred as `Vote<Subject>` from `Decision<Subject>::collect()` but the generic is not resolved.

## Running Locally

```bash
composer install
./test-versions.sh
```

## Requirements to Reproduce

- `level: max`
- `bleedingEdge.neon` included
- Function calls inside the closure body (for Example 1)
