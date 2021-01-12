<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest;

use PHPUnit\Framework\Constraint\IsType;
use ReflectionClass;

trait AttributeAssertionTrait
{
    protected function assertAttributeSame($expected, string $attributeName, object $object): void
    {
        $this->assertSame($expected, $this->getInternalProperty($attributeName, $object));
    }

    protected function assertAttributeNotSame($expected, string $attributeName, object $object): void
    {
        $this->assertNotSame($expected, $this->getInternalProperty($attributeName, $object));
    }

    protected function assertAttributeEquals($expected, string $attributeName, object $object): void
    {
        $this->assertEquals($expected, $this->getInternalProperty($attributeName, $object));
    }

    protected function assertAttributeInstanceOf(string $expected, string $attributeName, object $object): void
    {
        $this->assertInstanceOf($expected, $this->getInternalProperty($attributeName, $object));
    }

    protected function assertAttributeInternalType(string $expected, string $attributeName, object $object): void
    {
        $this->assertInternalType($expected, $this->getInternalProperty($attributeName, $object));
    }

    protected function assertAttributeEmpty(string $attributeName, object $object): void
    {
        $this->assertEmpty($this->getInternalProperty($attributeName, $object));
    }

    protected function assertInternalType(string $expected, $actual): void
    {
        $this->assertThat($actual, new IsType($expected));
    }

    private function getInternalProperty(string $attributeName, object $object)
    {
        $reflectionClass = new ReflectionClass($object);

        $property = $reflectionClass->getProperty($attributeName);
        $property->setAccessible(true);

        return $property->getValue($object);
    }
}
