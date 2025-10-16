# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

**typed-registry** is a dependency-free PHP library that provides strict type-safe access to configuration/registry values. The core principle is **zero coercion** - values must be exactly the expected type or an exception is thrown.

**Namespace:** `TypedRegistry\`
**PHP Version:** ≥8.3
**Quality Target:** PHPStan Level Max (10), zero errors, zero baseline

## Development Commands

### Testing
```bash
# Run all tests
vendor/bin/phpunit

# Run tests with detailed output
vendor/bin/phpunit --testdox

# Run specific test class
vendor/bin/phpunit tests/TypedRegistryTest.php

# Run specific test method
vendor/bin/phpunit --filter testGetStringReturnsString
```

### Static Analysis
```bash
# Run PHPStan at max level
vendor/bin/phpstan analyse

# PHPStan config is in phpstan.neon.dist
# Local overrides go in phpstan.neon (gitignored)
```

### Setup
```bash
# Install dependencies
composer install

# Update dependencies
composer update
```

## Architecture

### Core Abstraction: Provider Pattern

The library uses a simple **Provider** interface to decouple data sources from type validation:

```
Provider (interface) → TypedRegistry (facade) → Typed values
```

**Provider** implementations return `mixed` for any key. **TypedRegistry** validates and casts these to strict types.

### Built-in Providers

1. **ArrayProvider** - Array-backed (used in tests and simple use cases)
2. **CallbackProvider** - Wraps any callable for quick integrations
3. **CompositeProvider** - Chains multiple providers with fallback logic (returns first non-null)

### TypedRegistry: The Type Boundary

**TypedRegistry** has exactly 20 public methods organized into 5 groups:

1. **Primitives** (4): `getString()`, `getInt()`, `getBool()`, `getFloat()`
2. **Nullable** (4): `getNullableString()`, `getNullableInt()`, `getNullableBool()`, `getNullableFloat()`
3. **With Defaults** (4): `getStringOr()`, `getIntOr()`, `getBoolOr()`, `getFloatOr()`
4. **Lists** (4): `getStringList()`, `getIntList()`, `getBoolList()`, `getFloatList()`
5. **Maps** (4): `getStringMap()`, `getIntMap()`, `getBoolMap()`, `getFloatMap()`

### Strict Validation Rules

**Primitives:**
- Use PHP's `is_string()`, `is_int()`, `is_bool()`, `is_float()` - no coercion
- Example: `"123"` is NOT an int, `1` is NOT a float, `1` is NOT a bool

**Lists:**
- Must be arrays AND pass `array_is_list()` (sequential keys starting at 0)
- Every element validated individually
- Error messages include element position: `"key 'hosts[2]' must be string, got int(123)"`

**Maps:**
- Must be arrays with ALL string keys
- Every key AND value validated
- No position-specific errors (reports entire map as invalid)

**Nullables:**
- Accept `null` as a valid value
- Used when `null` has semantic meaning (not just "missing")

**Defaults (`getXxxOr`):**
- Never throw exceptions
- Return default on: missing key, null value, OR type mismatch
- Implemented as try/catch wrappers around strict getters

### Error Message Format

All `RegistryTypeError` messages follow this pattern:
```
"[typed-registry] key '{key}' must be {expected}, got {actual}"
```

Where `{actual}` is:
- `var_export()` for scalars/null: `'string'`, `123`, `NULL`, `true`
- `get_debug_type()` for complex types: `array`, `stdClass`, `Closure`

## Code Style & Conventions

### Required Patterns
- **Strict types:** Every file starts with `declare(strict_types=1);`
- **Final classes:** All concrete classes are `final`
- **Readonly when possible:** Use readonly properties or private with no setters
- **Explicit return types:** Every method has a return type (including `mixed`)
- **PHPDoc for arrays:** Use `@return list<T>` or `@return array<K,V>` for PHPStan

### Forbidden Patterns
- **No coercion:** Never use `(int)`, `(string)`, `intval()`, `filter_var()`, etc.
- **No inheritance:** No abstract classes, traits, or class hierarchies (composition over inheritance)
- **No dependencies:** Core package must have zero runtime dependencies
- **No nullable by default:** Only use nullable when `null` has meaning, not for "optional"

## Testing Strategy

### Test Coverage Requirements
- Every public method has a happy path test
- Every public method has type mismatch tests for all wrong types
- List/map getters test: not array, wrong element type at various positions
- Error message format validated in specific tests
- Providers tested for: existing values, null values, missing keys, chaining behavior

### Test File Organization
- `TypedRegistryTest.php` - All 20 methods of TypedRegistry (51 tests)
- `ProvidersTest.php` - All 3 built-in providers (12 tests)

### Running Focused Tests
When working on a specific method:
```bash
# Test just one getter type
vendor/bin/phpunit --filter getString

# Test error handling
vendor/bin/phpunit --filter Throws
```

## PHPStan Considerations

### Type Assertions
After validating collections, use PHPStan type assertions:
```php
/** @var list<string> $value */
return $value;
```

This tells PHPStan the exact type without changing runtime behavior.

### Import Functions
TypedRegistry imports all validation functions at the top:
```php
use function array_is_list;
use function is_string;
// etc.
```

This is for clarity and potential performance (avoids global namespace lookups).

## Design Philosophy

### What to Add
- New primitive types (if PHP adds them)
- New provider implementations (but consider external packages)
- Performance optimizations (if zero-cost)

### What NOT to Add
- Coercion logic (violates core principle)
- Schema validation (future `typed-registry-schema` package)
- Complex types (shapes, unions) - future `typed-registry-psl` package
- Default value configuration - keep explicit at call sites

### When to Throw vs. Return Default
- **Strict getters (`getXxx`)**: Always throw on type mismatch
- **Default getters (`getXxxOr`)**: Never throw, always return default
- **Nullable getters (`getNullableXxx`)**: Throw on wrong type, return null on null

## Future Extensions (Not Yet Implemented)

Per SPECIFICATION.md, these are planned as separate packages:
- `typed-registry-psl` - Shape/union types via PHP Standard Library
- `typed-registry-schema` - Schema validation and DTO mapping
- `typed-registry-laravel` - Laravel-specific providers and facades

Do not implement these in the core package.
