<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest;

use Generator;
use Mezzio\Exception\ContainerNotRegisteredException;
use Mezzio\Exception\ExceptionInterface;
use Mezzio\Exception\InvalidMiddlewareException;
use Mezzio\Exception\MissingDependencyException;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;

use function basename;
use function glob;
use function is_a;
use function strrpos;
use function substr;

class ExceptionTest extends TestCase
{
    public function exception() : Generator
    {
        $namespace = substr(ExceptionInterface::class, 0, strrpos(ExceptionInterface::class, '\\') + 1);

        $exceptions = glob(__DIR__ . '/../src/Exception/*.php');
        foreach ($exceptions as $exception) {
            $class = substr(basename($exception), 0, -4);

            yield $class => [$namespace . $class];
        }
    }

    /**
     * @dataProvider exception
     */
    public function testExceptionIsInstanceOfExceptionInterface(string $exception) : void
    {
        $this->assertStringContainsString('Exception', $exception);
        $this->assertTrue(is_a($exception, ExceptionInterface::class, true));
    }

    public function containerException() : Generator
    {
        yield InvalidMiddlewareException::class => [InvalidMiddlewareException::class];
        yield MissingDependencyException::class => [MissingDependencyException::class];
    }

    /**
     * @dataProvider containerException
     */
    public function testExceptionIsInstanceOfContainerExceptionInterface(string $exception) : void
    {
        $this->assertTrue(is_a($exception, ContainerExceptionInterface::class, true));
    }

    public function testContainerNotRegisteredExceptionForMiddlewareService()
    {
        $exception = ContainerNotRegisteredException::forMiddlewareService('foo');

        $this->assertSame(
            'Cannot marshal middleware by service name "foo"; no container registered',
            $exception->getMessage()
        );
    }
}
