<?php

declare(strict_types=1);

namespace TypedRegistry\Tests;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TypedRegistry\ArrayProvider;
use TypedRegistry\RegistryTypeError;
use TypedRegistry\TypedRegistry;

final class TypedRegistryTest extends TestCase
{
    // ==================== Primitive Getters - Happy Paths ====================

    public function testGetStringReturnsString(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'value']));
        self::assertSame('value', $registry->getString('key'));
    }

    public function testGetIntReturnsInt(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 42]));
        self::assertSame(42, $registry->getInt('key'));
    }

    public function testGetBoolReturnsBool(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => true]));
        self::assertTrue($registry->getBool('key'));
    }

    public function testGetFloatReturnsFloat(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 3.14]));
        self::assertSame(3.14, $registry->getFloat('key'));
    }

    // ==================== Primitive Getters - Type Mismatches ====================

    public function testGetStringThrowsOnNonString(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 123]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be string, got 123");

        $registry->getString('key');
    }

    public function testGetIntThrowsOnNonInt(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => '123']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be int, got '123'");

        $registry->getInt('key');
    }

    public function testGetBoolThrowsOnNonBool(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 1]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be bool, got 1");

        $registry->getBool('key');
    }

    public function testGetFloatThrowsOnNonFloat(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 42]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be float, got 42");

        $registry->getFloat('key');
    }

    public function testGetStringThrowsOnNull(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => null]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be string, got NULL");

        $registry->getString('key');
    }

    // ==================== Nullable Getters ====================

    public function testGetNullableStringReturnsString(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'value']));
        self::assertSame('value', $registry->getNullableString('key'));
    }

    public function testGetNullableStringReturnsNull(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => null]));
        self::assertNull($registry->getNullableString('key'));
    }

    public function testGetNullableStringThrowsOnWrongType(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 123]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be string|null, got 123");

        $registry->getNullableString('key');
    }

    public function testGetNullableIntReturnsInt(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 42]));
        self::assertSame(42, $registry->getNullableInt('key'));
    }

    public function testGetNullableIntReturnsNull(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => null]));
        self::assertNull($registry->getNullableInt('key'));
    }

    public function testGetNullableBoolReturnsBool(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => false]));
        self::assertFalse($registry->getNullableBool('key'));
    }

    public function testGetNullableBoolReturnsNull(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => null]));
        self::assertNull($registry->getNullableBool('key'));
    }

    public function testGetNullableIntThrowsOnWrongType(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => '42']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be int|null, got '42'");

        $registry->getNullableInt('key');
    }

    public function testGetNullableBoolThrowsOnWrongType(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 1]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be bool|null, got 1");

        $registry->getNullableBool('key');
    }

    public function testGetNullableFloatReturnsFloat(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 2.71]));
        self::assertSame(2.71, $registry->getNullableFloat('key'));
    }

    public function testGetNullableFloatReturnsNull(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => null]));
        self::assertNull($registry->getNullableFloat('key'));
    }

    public function testGetNullableFloatThrowsOnWrongType(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 42]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be float|null, got 42");

        $registry->getNullableFloat('key');
    }

    // ==================== Getters with Defaults ====================

    public function testGetStringOrReturnsValue(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'value']));
        self::assertSame('value', $registry->getStringOr('key', 'default'));
    }

    public function testGetStringOrReturnsDefaultOnMissing(): void
    {
        $registry = new TypedRegistry(new ArrayProvider([]));
        self::assertSame('default', $registry->getStringOr('key', 'default'));
    }

    public function testGetStringOrReturnsDefaultOnTypeMismatch(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 123]));
        self::assertSame('default', $registry->getStringOr('key', 'default'));
    }

    public function testGetIntOrReturnsValue(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 42]));
        self::assertSame(42, $registry->getIntOr('key', 99));
    }

    public function testGetIntOrReturnsDefaultOnMissing(): void
    {
        $registry = new TypedRegistry(new ArrayProvider([]));
        self::assertSame(99, $registry->getIntOr('key', 99));
    }

    public function testGetIntOrReturnsDefaultOnTypeMismatch(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-int']));
        self::assertSame(99, $registry->getIntOr('key', 99));
    }

    public function testGetBoolOrReturnsValue(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => true]));
        self::assertTrue($registry->getBoolOr('key', false));
    }

    public function testGetBoolOrReturnsDefaultOnMissing(): void
    {
        $registry = new TypedRegistry(new ArrayProvider([]));
        self::assertFalse($registry->getBoolOr('key', false));
    }

    public function testGetBoolOrReturnsDefaultOnTypeMismatch(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 1]));
        self::assertFalse($registry->getBoolOr('key', false));
    }

    public function testGetFloatOrReturnsValue(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 3.14]));
        self::assertSame(3.14, $registry->getFloatOr('key', 2.71));
    }

    public function testGetFloatOrReturnsDefaultOnMissing(): void
    {
        $registry = new TypedRegistry(new ArrayProvider([]));
        self::assertSame(2.71, $registry->getFloatOr('key', 2.71));
    }

    public function testGetFloatOrReturnsDefaultOnTypeMismatch(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 42]));
        self::assertSame(2.71, $registry->getFloatOr('key', 2.71));
    }

    // ==================== List Getters - Happy Paths ====================

    public function testGetStringListReturnsStringList(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a', 'b', 'c']]));
        self::assertSame(['a', 'b', 'c'], $registry->getStringList('key'));
    }

    public function testGetStringListReturnsEmptyList(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => []]));
        self::assertSame([], $registry->getStringList('key'));
    }

    public function testGetIntListReturnsIntList(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => [1, 2, 3]]));
        self::assertSame([1, 2, 3], $registry->getIntList('key'));
    }

    public function testGetBoolListReturnsBoolList(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => [true, false, true]]));
        self::assertSame([true, false, true], $registry->getBoolList('key'));
    }

    public function testGetFloatListReturnsFloatList(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => [1.1, 2.2, 3.3]]));
        self::assertSame([1.1, 2.2, 3.3], $registry->getFloatList('key'));
    }

    // ==================== List Getters - Validation Errors ====================

    public function testGetStringListThrowsOnNonArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-array']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be list<string>, got 'not-an-array'");

        $registry->getStringList('key');
    }

    public function testGetStringListThrowsOnAssociativeArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => 'value']]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be list<string>");

        $registry->getStringList('key');
    }

    public function testGetStringListThrowsOnWrongElementType(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a', 'b', 123]]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key[2]' must be string, got 123");

        $registry->getStringList('key');
    }

    public function testGetIntListThrowsOnNonArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-array']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be list<int>, got 'not-an-array'");

        $registry->getIntList('key');
    }

    public function testGetIntListThrowsOnWrongElementType(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => [1, 2, '3']]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key[2]' must be int, got '3'");

        $registry->getIntList('key');
    }

    public function testGetBoolListThrowsOnNonArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-array']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be list<bool>, got 'not-an-array'");

        $registry->getBoolList('key');
    }

    public function testGetBoolListThrowsOnWrongElementType(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => [true, false, 1]]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key[2]' must be bool, got 1");

        $registry->getBoolList('key');
    }

    public function testGetFloatListThrowsOnNonArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-array']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be list<float>, got 'not-an-array'");

        $registry->getFloatList('key');
    }

    public function testGetFloatListThrowsOnWrongElementType(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => [1.1, 2.2, 3]]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key[2]' must be float, got 3");

        $registry->getFloatList('key');
    }

    // ==================== Map Getters - Happy Paths ====================

    public function testGetStringMapReturnsStringMap(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => 'x', 'b' => 'y']]));
        self::assertSame(['a' => 'x', 'b' => 'y'], $registry->getStringMap('key'));
    }

    public function testGetStringMapReturnsEmptyMap(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => []]));
        self::assertSame([], $registry->getStringMap('key'));
    }

    public function testGetIntMapReturnsIntMap(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => 1, 'b' => 2]]));
        self::assertSame(['a' => 1, 'b' => 2], $registry->getIntMap('key'));
    }

    public function testGetBoolMapReturnsBoolMap(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => true, 'b' => false]]));
        self::assertSame(['a' => true, 'b' => false], $registry->getBoolMap('key'));
    }

    public function testGetFloatMapReturnsFloatMap(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => 1.5, 'b' => 2.5]]));
        self::assertSame(['a' => 1.5, 'b' => 2.5], $registry->getFloatMap('key'));
    }

    // ==================== Map Getters - Validation Errors ====================

    public function testGetStringMapThrowsOnNonArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-array']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,string>, got 'not-an-array'");

        $registry->getStringMap('key');
    }

    public function testGetStringMapThrowsOnNonStringKey(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => [0 => 'value']]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,string>");

        $registry->getStringMap('key');
    }

    public function testGetStringMapThrowsOnNonStringValue(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => 123]]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,string>");

        $registry->getStringMap('key');
    }

    public function testGetIntMapThrowsOnNonArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-array']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,int>, got 'not-an-array'");

        $registry->getIntMap('key');
    }

    public function testGetIntMapThrowsOnNonIntValue(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => '123']]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,int>");

        $registry->getIntMap('key');
    }

    public function testGetBoolMapThrowsOnNonArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-array']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,bool>, got 'not-an-array'");

        $registry->getBoolMap('key');
    }

    public function testGetBoolMapThrowsOnNonBoolValue(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => 1]]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,bool>");

        $registry->getBoolMap('key');
    }

    public function testGetFloatMapThrowsOnNonArray(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'not-an-array']));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,float>, got 'not-an-array'");

        $registry->getFloatMap('key');
    }

    public function testGetFloatMapThrowsOnNonFloatValue(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['a' => 123]]));

        $this->expectException(RegistryTypeError::class);
        $this->expectExceptionMessage("[typed-registry] key 'key' must be map<string,float>");

        $registry->getFloatMap('key');
    }

    // ==================== Error Message Format ====================

    public function testErrorMessageFormatsScalars(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => 'wrong']));

        try {
            $registry->getInt('key');
            self::fail('Expected RegistryTypeError');
        } catch (RegistryTypeError $e) {
            self::assertStringContainsString("'wrong'", $e->getMessage());
        }
    }

    public function testErrorMessageFormatsComplexTypes(): void
    {
        $registry = new TypedRegistry(new ArrayProvider(['key' => ['array']]));

        try {
            $registry->getString('key');
            self::fail('Expected RegistryTypeError');
        } catch (RegistryTypeError $e) {
            self::assertStringContainsString('array', $e->getMessage());
        }
    }
}
