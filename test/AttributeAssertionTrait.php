<?php

declare(strict_types=1);

namespace MezzioTest;

use PHPUnit\Framework\Constraint\IsType;
use ReflectionClass;

trait AttributeAssertionTrait
{
    protected function assertAttributeSame($expected, string $actualAttributeName, object $actualClassOrObject): void
    {
        $reflectionClass = new ReflectionClass($actualClassOrObject);

        $property = $reflectionClass->getProperty($actualAttributeName);
        $property->setAccessible(true);

        $this->assertSame($expected, $property->getValue($actualClassOrObject));
    }

    protected function assertAttributeNotSame($expected, string $actualAttributeName, object $actualClassOrObject): void
    {
        $reflectionClass = new ReflectionClass($actualClassOrObject);

        $property = $reflectionClass->getProperty($actualAttributeName);
        $property->setAccessible(true);

        $this->assertNotSame($expected, $property->getValue($actualClassOrObject));
    }

    protected function assertAttributeEquals($expected, string $actualAttributeName, object $actualClassOrObject): void
    {
        $reflectionClass = new ReflectionClass($actualClassOrObject);

        $property = $reflectionClass->getProperty($actualAttributeName);
        $property->setAccessible(true);

        $this->assertEquals($expected, $property->getValue($actualClassOrObject));
    }

    protected function assertAttributeEmpty(string $haystackAttributeName, $haystackClassOrObject): void
    {
        $reflectionClass = new ReflectionClass($haystackClassOrObject);

        $property = $reflectionClass->getProperty($haystackAttributeName);
        $property->setAccessible(true);

        $this->assertEmpty($property->getValue($haystackClassOrObject));
    }

    protected function assertAttributeInstanceOf(string $expected, string $attributeName, $classOrObject): void
    {
        $reflectionClass = new ReflectionClass($classOrObject);

        $property = $reflectionClass->getProperty($attributeName);
        $property->setAccessible(true);

        $this->assertInstanceOf($expected, $property->getValue($classOrObject));
    }

    protected function assertAttributeInternalType(string $expected, string $attributeName, $classOrObject): void
    {
        $reflectionClass = new ReflectionClass($classOrObject);

        $property = $reflectionClass->getProperty($attributeName);
        $property->setAccessible(true);

        $this->assertInternalType($expected, $property->getValue($classOrObject));
    }

    protected function assertInternalType(string $expected, $actual): void
    {
        $this->assertThat($actual, new IsType($expected));
    }
}
