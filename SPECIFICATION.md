**TL;DR:** Ship a tiny, dependency-free core called **`typed-registry`**. Expose a `Provider` interface plus a `TypedRegistry` facade with strict (non-coercive) getters for primitives and common collections. Throw a single `RegistryTypeError` on mismatch. Include a couple of built-in providers (`ArrayProvider`, `CallbackProvider`, optional `CompositeProvider`) and keep everything PHPStan-level-10 friendly via precise return types and phpdoc where needed. SemVer, MIT, PHPUnit tests, PHP ≥ 8.3.

---

### 1) Package mission and scope

* **Goal:** Provide a strict, dependency-free boundary that turns `mixed` reads from any registry/config source into typed values usable in strictly typed codebases.
* **Non-goals (for core):** Coercion (“1” → `1`), schema validation, complex shapes/unions, external type systems, PSR container abstractions. Those can live in optional adapters later.

---

### 2) Name, repo, license, PHP version

* **Package:** `your-vendor/typed-registry`
* **Namespace:** `YourVendor\TypedRegistry`
* **License:** MIT
* **PHP:** ≥ 8.3
* **SemVer:** yes (breaking changes only in new majors)

---

### 3) Public API (v0.1)

**Provider interface (pluggable source)**

```php
<?php
declare(strict_types=1);

namespace YourVendor\TypedRegistry;

/** Returns mixed for a key. Key format is provider-defined (string). */
interface Provider
{
    /** @return mixed */
    public function get(string $key);
}
```

**Core exception**

```php
<?php
declare(strict_types=1);

namespace YourVendor\TypedRegistry;

final class RegistryTypeError extends \RuntimeException {}
```

**Typed facade (strict, non-coercive)**

```php
<?php
declare(strict_types=1);

namespace YourVendor\TypedRegistry;

final class TypedRegistry
{
    public function __construct(private Provider $provider) {}

    // Primitives
    public function getString(string $key): string { /* strict */ }
    public function getInt(string $key): int { /* strict */ }
    public function getBool(string $key): bool { /* strict */ }
    public function getFloat(string $key): float { /* strict */ }

    // Nullable variants (if the provider legitimately stores null)
    public function getNullableString(string $key): ?string { /* strict-or-null */ }
    public function getNullableInt(string $key): ?int { /* strict-or-null */ }
    public function getNullableBool(string $key): ?bool { /* strict-or-null */ }
    public function getNullableFloat(string $key): ?float { /* strict-or-null */ }

    // With defaults (shortcut: if value is absent or type-mismatched, return default)
    public function getStringOr(string $key, string $default): string { /* … */ }
    public function getIntOr(string $key, int $default): int { /* … */ }
    public function getBoolOr(string $key, bool $default): bool { /* … */ }
    public function getFloatOr(string $key, float $default): float { /* … */ }

    /** @return list<string> */
    public function getStringList(string $key): array { /* list-of-string */ }
    /** @return list<int> */
    public function getIntList(string $key): array { /* list-of-int */ }
    /** @return list<bool> */
    public function getBoolList(string $key): array { /* list-of-bool */ }
    /** @return list<float> */
    public function getFloatList(string $key): array { /* list-of-float */ }

    /** @return array<string,string> */
    public function getStringMap(string $key): array { /* map<string,string> */ }
    /** @return array<string,int> */
    public function getIntMap(string $key): array { /* map<string,int> */ }
    /** @return array<string,bool> */
    public function getBoolMap(string $key): array { /* map<string,bool> */ }
    /** @return array<string,float> */
    public function getFloatMap(string $key): array { /* map<string,float> */ }
}
```

**Built-in providers**

```php
<?php
namespace YourVendor\TypedRegistry;

/** Array-backed provider (useful for tests or preloaded config) */
final class ArrayProvider implements Provider
{
    public function __construct(private array $data) {}
    public function get(string $key) { return $this->data[$key] ?? null; }
}

/** Closure-backed provider for quick integrations or adapters */
final class CallbackProvider implements Provider
{
    /** @param callable(string):mixed $getter */
    public function __construct(private $getter) {}
    public function get(string $key) { return ($this->getter)($key); }
}

/** Optional: try several providers in order until one returns a non-null value */
final class CompositeProvider implements Provider
{
    /** @param list<Provider> $providers */
    public function __construct(private array $providers) {}
    public function get(string $key)
    {
        foreach ($this->providers as $p) {
            $v = $p->get($key);
            if ($v !== null) { return $v; }
        }
        return null;
    }
}
```

**Strictness & errors**

* All `getXxx()` methods are **non-coercive**: if the value isn’t exactly the requested type (including all list/map elements), the method throws `RegistryTypeError`.
* Message format suggestion:
  `"[typed-registry] key 'app.port' must be int, got string('8080')"`
* `getXxxOr($default)` returns the default when:

    * value is `null`, or
    * type mismatches; no exception thrown in this path.
* Use `getNullableXxx()` only if `null` is a legitimate stored value you want to distinguish from “bad type”.

---

### 4) Implementation sketch (dependency-free, strict)

```php
<?php
declare(strict_types=1);

namespace YourVendor\TypedRegistry;

final class TypedRegistry
{
    public function __construct(private Provider $provider) {}

    public function getString(string $key): string {
        $v = $this->provider->get($key);
        if (!is_string($v)) {
            throw new RegistryTypeError($this->msg($key, 'string', $v));
        }
        return $v;
    }

    public function getInt(string $key): int {
        $v = $this->provider->get($key);
        if (!is_int($v)) {
            throw new RegistryTypeError($this->msg($key, 'int', $v));
        }
        return $v;
    }

    public function getBool(string $key): bool {
        $v = $this->provider->get($key);
        if (!is_bool($v)) {
            throw new RegistryTypeError($this->msg($key, 'bool', $v));
        }
        return $v;
    }

    public function getFloat(string $key): float {
        $v = $this->provider->get($key);
        if (!is_float($v)) {
            throw new RegistryTypeError($this->msg($key, 'float', $v));
        }
        return $v;
    }

    public function getNullableString(string $key): ?string {
        $v = $this->provider->get($key);
        if ($v === null) { return null; }
        if (!is_string($v)) {
            throw new RegistryTypeError($this->msg($key, 'string|null', $v));
        }
        return $v;
    }

    public function getStringOr(string $key, string $default): string {
        try { return $this->getString($key); } catch (RegistryTypeError) { return $default; }
    }
    public function getIntOr(string $key, int $default): int {
        try { return $this->getInt($key); } catch (RegistryTypeError) { return $default; }
    }
    public function getBoolOr(string $key, bool $default): bool {
        try { return $this->getBool($key); } catch (RegistryTypeError) { return $default; }
    }
    public function getFloatOr(string $key, float $default): float {
        try { return $this->getFloat($key); } catch (RegistryTypeError) { return $default; }
    }

    /** @return list<string> */
    public function getStringList(string $key): array {
        $v = $this->provider->get($key);
        if (!is_array($v) || !array_is_list($v)) {
            throw new RegistryTypeError($this->msg($key, 'list<string>', $v));
        }
        foreach ($v as $i => $item) {
            if (!is_string($item)) {
                throw new RegistryTypeError($this->msg("{$key}[{$i}]", 'string', $item));
            }
        }
        /** @var list<string> $v */ return $v;
    }

    /** @return array<string,string> */
    public function getStringMap(string $key): array {
        $v = $this->provider->get($key);
        if (!is_array($v)) {
            throw new RegistryTypeError($this->msg($key, 'map<string,string>', $v));
        }
        foreach ($v as $k => $item) {
            if (!is_string($k) || !is_string($item)) {
                throw new RegistryTypeError($this->msg($key, 'map<string,string>', $v));
            }
        }
        /** @var array<string,string> $v */ return $v;
    }

    private function msg(string $key, string $expected, mixed $got): string
    {
        $repr = is_scalar($got) || $got === null ? var_export($got, true) : get_debug_type($got);
        return "[typed-registry] key '{$key}' must be {$expected}, got {$repr}";
    }
}
```

---

### 5) Example integration

**Wrap a 3rd-party static registry**

```php
<?php
namespace App;

use YourVendor\TypedRegistry\TypedRegistry;
use YourVendor\TypedRegistry\Provider;

final class SomeLibProvider implements Provider
{
    public function get(string $key) { return \Some\Library\Config::get($key); }
}

$reg = new TypedRegistry(new SomeLibProvider());
$debug = $reg->getBoolOr('app.debug', false);
$port  = $reg->getInt('app.port');     // throws RegistryTypeError if not int
$hosts = $reg->getStringList('app.hosts');
```

**Use arrays for tests**

```php
$reg = new TypedRegistry(new \YourVendor\TypedRegistry\ArrayProvider([
    'app.debug' => true,
    'app.hosts' => ['a.local', 'b.local'],
]));
```

**Layered (override) lookups**

```php
$reg = new TypedRegistry(new \YourVendor\TypedRegistry\CompositeProvider([
    new ArrayProvider($envOverrides),
    new CallbackProvider(fn(string $k) => \Some\Library\Config::get($k)),
]));
```

---

### 6) Composer skeleton

```json
{
  "name": "your-vendor/typed-registry",
  "description": "Dependency-free typed facade over mixed registries/config sources for PHPStan-level codebases.",
  "type": "library",
  "license": "MIT",
  "autoload": { "psr-4": { "YourVendor\\\\TypedRegistry\\\\": "src/" } },
  "require": {
    "php": ">=8.3"
  },
  "require-dev": {
    // phpunit, phpstan ...
  },
  "minimum-stability": "stable",
  "keywords": [
    "phpstan",
    "typed",
    "registry",
    "config",
    "strict",
    "static analysis"
  ],
  "suggest": {
    "your-vendor/typed-registry-psl": "Optional adapter for shape/union types & coercion (future).",
    "your-vendor/typed-registry-webmozart": "Optional adapter for assertion-based guards (future)."
  }
}
```

---

### 7) Directory layout

```
/src
  Provider.php
  RegistryTypeError.php
  TypedRegistry.php
  ArrayProvider.php
  CallbackProvider.php
  CompositeProvider.php
/tests
  TypedRegistryTest.php
  ProvidersTest.php
phpstan.neon.dist
phpunit.xml
README.md
LICENSE
```

---

### 8) PHPStan & testing

* **PHPStan:** target level 10. No baseline. Enable `checkGenericClassInNonGenericObjectType: true` and `treatPhpDocTypesAsCertain: true`.
* **PHPUnit:** cover happy paths and every failure mode with the exact exception messages (keys, paths, and positions for list/map elements).

---

### 9) Design choices (explicit)

* **Strict only:** no implicit coercion; code either returns the correct type or throws.
* **Defaults are explicit:** `getXxxOr()` documents fallbacks at call sites and keeps behavior grep-able.
* **Collections are validated deeply:** every element and map pair is type-checked.
* **No opinions on key syntax:** the provider defines what “key” means (dot paths, env names, array keys).
* **Thin surface area:** primitives + lists + maps cover most real-world needs without pulling in a type framework.

---

### 10) Roadmap (post-v0.1, separate packages)

* `typed-registry-psl`: add `get($key, TypeInterface<T>) : T` for shapes/unions/coercion via PSL Types.
* `typed-registry-schema`: opt-in schema validation and mapping to DTOs (still independent of core).
* `typed-registry-laravel`: provider for Laravel config repository; `env()`/`config()` bridges.

---

### 11) README opening (draft)

````
# typed-registry

A dependency-free, strict facade that turns `mixed` config/registry values into real PHP types.
No magic. No coercion. PHPStan-ready.

```php
$reg = new TypedRegistry(new ArrayProvider([
    'app.debug' => true,
    'app.hosts' => ['a.local', 'b.local'],
]));

$debug = $reg->getBool('app.debug');         // bool
$hosts = $reg->getStringList('app.hosts');   // list<string>
$port  = $reg->getIntOr('app.port', 8080);   // default if missing/wrong type
````
