# typed-registry

A dependency-free, strict facade that turns `mixed` config/registry values into real PHP types.
No magic. No coercion. PHPStan-ready.

[![PHPStan Level](https://img.shields.io/badge/PHPStan-max-blue.svg)](https://phpstan.org/)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

## Why?

Modern PHP codebases use strict types and static analysis (PHPStan, Psalm), but configuration systems often return `mixed` values. This package provides a **strict, non-coercive boundary** between your config sources and typed code:

- **No implicit coercion** - `"123"` stays a string, won't become `int(123)`
- **Deep validation** - Lists and maps validate every element
- **Explicit defaults** - `getIntOr($key, 8080)` makes fallback behavior grep-able
- **Pluggable sources** - Wrap any config system via a simple `Provider` interface
- **PHPStan Level 10** - Zero errors, precise return types

## Installation

```bash
composer require alexkart/typed-registry
```

Requires PHP 8.3 or later.

## Quick Start

```php
use TypedRegistry\TypedRegistry;
use TypedRegistry\ArrayProvider;

$registry = new TypedRegistry(new ArrayProvider([
    'app.debug' => true,
    'app.port' => 8080,
    'app.hosts' => ['web1.local', 'web2.local'],
]));

$debug = $registry->getBool('app.debug');         // bool(true)
$port  = $registry->getInt('app.port');           // int(8080)
$hosts = $registry->getStringList('app.hosts');   // list<string>

// With defaults (no exception on missing/wrong type)
$timeout = $registry->getIntOr('app.timeout', 30); // int(30)
```

## Core Concepts

### 1. Provider Interface

Any config source can be wrapped by implementing the `Provider` interface:

```php
interface Provider
{
    public function get(string $key): mixed;
}
```

**Built-in providers:**

- **`ArrayProvider`** - Array-backed (great for tests or preloaded config)
- **`CallbackProvider`** - Wrap any callable
- **`CompositeProvider`** - Fallback chain (env → config → defaults)

### 2. Strict Type Checking

All `getXxx()` methods validate types **without coercion**:

```php
$registry = new TypedRegistry(new ArrayProvider(['port' => '8080']));

$registry->getInt('port'); // ❌ Throws RegistryTypeError
// "[typed-registry] key 'port' must be int, got '8080'"
```

To handle this, either:
- Store the correct type: `['port' => 8080]`
- Use a default: `$registry->getIntOr('port', 8080)`

### 3. Collections

Lists and maps are validated deeply:

```php
// Lists (sequential arrays)
$registry->getStringList('app.hosts');  // ✅ ['a', 'b', 'c']
$registry->getIntList('app.ids');       // ✅ [1, 2, 3]

// Maps (associative arrays with string keys)
$registry->getStringMap('app.labels'); // ✅ ['env' => 'prod', 'tier' => 'web']
$registry->getIntMap('app.limits');    // ✅ ['max' => 100, 'min' => 10]

// Invalid examples
$registry->getStringList('key'); // ❌ If value is ['a', 123, 'c']
// "[typed-registry] key 'key[1]' must be string, got 123"

$registry->getStringMap('key'); // ❌ If value is [0 => 'value']
// "[typed-registry] key 'key' must be map<string,string>, got array"
```

## API Reference

### Primitive Getters

| Method | Return Type | Throws on Type Mismatch |
|--------|-------------|-------------------------|
| `getString(string $key)` | `string` | ✅ |
| `getInt(string $key)` | `int` | ✅ |
| `getBool(string $key)` | `bool` | ✅ |
| `getFloat(string $key)` | `float` | ✅ |

### Nullable Variants

Accept `null` as a legitimate value:

| Method | Return Type | Throws on Type Mismatch |
|--------|-------------|-------------------------|
| `getNullableString(string $key)` | `?string` | ✅ (unless null or string) |
| `getNullableInt(string $key)` | `?int` | ✅ (unless null or int) |
| `getNullableBool(string $key)` | `?bool` | ✅ (unless null or bool) |
| `getNullableFloat(string $key)` | `?float` | ✅ (unless null or float) |

### Getters with Defaults

Return the default value if key is missing or type mismatches (no exception):

| Method | Return Type | Throws |
|--------|-------------|--------|
| `getStringOr(string $key, string $default)` | `string` | ❌ |
| `getIntOr(string $key, int $default)` | `int` | ❌ |
| `getBoolOr(string $key, bool $default)` | `bool` | ❌ |
| `getFloatOr(string $key, float $default)` | `float` | ❌ |

### List Getters

Return sequential arrays (validated with `array_is_list()`):

| Method | Return Type |
|--------|-------------|
| `getStringList(string $key)` | `list<string>` |
| `getIntList(string $key)` | `list<int>` |
| `getBoolList(string $key)` | `list<bool>` |
| `getFloatList(string $key)` | `list<float>` |

### Map Getters

Return associative arrays with string keys:

| Method | Return Type |
|--------|-------------|
| `getStringMap(string $key)` | `array<string, string>` |
| `getIntMap(string $key)` | `array<string, int>` |
| `getBoolMap(string $key)` | `array<string, bool>` |
| `getFloatMap(string $key)` | `array<string, float>` |

## Usage Examples

### Example 1: Wrap Any Config System

```php
use TypedRegistry\TypedRegistry;
use TypedRegistry\Provider;

// Wrap your existing config library/registry
final class SomeExternalLibraryConfigProvider implements Provider
{
    public function get(string $key): mixed
    {
        // Adapt any existing config/registry system
        return \Some\Library\Config::get($key);
    }
}

$registry = new TypedRegistry(new SomeExternalLibraryConfigProvider());
$debug = $registry->getBool('app.debug');
$hosts = $registry->getStringList('app.allowed_hosts');
```

### Example 2: Composite Provider (Fallback Chain)

Environment variables → Config file → Defaults:

```php
use TypedRegistry\TypedRegistry;
use TypedRegistry\ArrayProvider;
use TypedRegistry\CallbackProvider;
use TypedRegistry\CompositeProvider;

$registry = new TypedRegistry(new CompositeProvider([
    new CallbackProvider(fn($k) => $_ENV[$k] ?? null),           // Environment
    new ArrayProvider(['app.port' => 8080, 'app.debug' => false]), // Config
    new ArrayProvider(['app.timeout' => 30]),                    // Defaults
]));

// Will use $_ENV['app.port'] if set, otherwise 8080 from config
$port = $registry->getInt('app.port');
```

### Example 3: Laravel Environment Variables with Type Casting

Laravel's `Illuminate\Support\Env` class handles booleans (`"true"` → `true`) and nulls (`"null"` → `null`), but numeric strings remain strings (`"8080"` → `"8080"`). If you need automatic type casting for numeric environment variables, here's a custom provider:

```php
use Illuminate\Support\Env;
use TypedRegistry\Provider;
use TypedRegistry\TypedRegistry;

final class EnvProvider implements Provider
{
    public function get(string $key): mixed
    {
        $value = Env::get($key);

        // If not a string, return as-is (booleans/nulls already handled by Env)
        if (!is_string($value)) {
            return $value;
        }

        // Only cast numeric strings
        if (!is_numeric($value)) {
            return $value;
        }

        // Cast to int if it represents a whole number
        if ((string) (int) $value === $value) {
            return (int) $value;
        }

        // Cast to float for decimals and scientific notation
        return (float) $value;
    }
}

// Usage
$env = new TypedRegistry(new EnvProvider());
$debug = $env->getBool('APP_DEBUG');      // "true" → bool(true)
$port = $env->getInt('APP_PORT');         // "8080" → int(8080)
$timeout = $env->getFloat('TIMEOUT');     // "2.5" → float(2.5)
$name = $env->getString('APP_NAME');      // "Laravel" → "Laravel"
```

> **Note:** This adapter performs type coercion, which differs from typed-registry's strict validation philosophy. Use it when you trust your environment variable format. Alternatively, the **`alexkart/typed-registry-laravel`** package (coming soon) provides this and other Laravel-specific integrations out of the box.

### Example 4: Testing with ArrayProvider

```php
use PHPUnit\Framework\TestCase;
use TypedRegistry\TypedRegistry;
use TypedRegistry\ArrayProvider;

final class MyServiceTest extends TestCase
{
    public function testServiceUsesConfiguredPort(): void
    {
        $registry = new TypedRegistry(new ArrayProvider([
            'service.host' => 'localhost',
            'service.port' => 9000,
            'service.ssl' => true,
        ]));

        $service = new MyService($registry);

        self::assertSame('https://localhost:9000', $service->getBaseUrl());
    }
}
```

## Error Handling

When type validation fails, `RegistryTypeError` (extends `RuntimeException`) is thrown:

```php
use TypedRegistry\RegistryTypeError;

try {
    $registry->getInt('app.port');
} catch (RegistryTypeError $e) {
    // Message format: "[typed-registry] key 'app.port' must be int, got '8080'"
    logger()->error($e->getMessage());
}
```

For graceful degradation, use the `getXxxOr()` variants:

```php
$timeout = $registry->getIntOr('app.timeout', 30); // Never throws
```

## Design Philosophy

### What This Library Does

- Provides strict type boundaries around `mixed` config sources
- Validates primitives, lists, and maps without coercion
- Enables PHPStan Level 10 compliance in config-heavy code
- Keeps implementation dependency-free (~250 LOC)

### What This Library Doesn't Do

- **Coercion** - Use a dedicated validation library if you need `"123"` → `123`
- **Schema validation** - For DTOs/shapes, see future `typed-registry-psl` adapter
- **Config file parsing** - This library consumes already-loaded config
- **PSR container** - Not a service locator, strictly config/registry access

## Development

```bash
# Install dependencies
composer install

# Run tests
composer test
# or: vendor/bin/phpunit

# Run static analysis
composer phpstan
# or: vendor/bin/phpstan analyse
```

**Quality Standards:**
- PHPStan Level: Max (10) with strict rules + bleeding edge
- Test Coverage: 100% (75 tests, 98 assertions)
- PHP Version: ≥8.3
- Dependencies: Zero (core package)

## Roadmap

Future optional packages (not required for core usage):

- **`alexkart/typed-registry-laravel`** - Laravel-specific providers (Env with casting, Config, etc.) and service provider
- **`alexkart/typed-registry-psl`** - Shape/union types via PHP Standard Library Types
- **`alexkart/typed-registry-schema`** - Schema validation and DTO mapping

## Contributing

Contributions are welcome! Please ensure:

1. All tests pass (`vendor/bin/phpunit`)
2. PHPStan Level 10 passes (`vendor/bin/phpstan analyse`)
3. Code follows existing style (strict types, explicit return types)

## License

MIT License. See [LICENSE](LICENSE) for details.

## Credits

Maintained by the TypedRegistry contributors.

---

**Questions?** Open an issue on GitHub.
**Need coercion?** Check out [webmozart/assert](https://github.com/webmozarts/assert) or [azjezz/psl](https://github.com/azjezz/psl).
