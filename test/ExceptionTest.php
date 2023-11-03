<?php

declare(strict_types=1);

namespace MezzioTest;

use Generator;
use Mezzio\Exception\ContainerNotRegisteredException;
use Mezzio\Exception\ExceptionInterface;
use Mezzio\Exception\InvalidMiddlewareException;
use Mezzio\Exception\MissingDependencyException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Throwable;

use function basename;
use function glob;
use function is_a;
use function strrpos;
use function substr;

class ExceptionTest extends TestCase
{
    public static function exception(): Generator
    {
        $namespace = substr(ExceptionInterface::class, 0, strrpos(ExceptionInterface::class, '\\') + 1);

        $exceptions = glob(__DIR__ . '/../src/Exception/*.php');
        foreach ($exceptions as $exception) {
            $class = substr(basename($exception), 0, -4);

            yield $class => [$namespace . $class];
        }
    }

    #[DataProvider('exception')]
    public function testExceptionIsInstanceOfExceptionInterface(string $exception): void
    {
        $this->assertStringContainsString('Exception', $exception);
        $this->assertTrue(is_a($exception, ExceptionInterface::class, true));
    }

    /** @return Generator<class-string<Throwable>, array{0: class-string<Throwable>}> */
    public static function containerException(): Generator
    {
        yield InvalidMiddlewareException::class => [InvalidMiddlewareException::class];
        yield MissingDependencyException::class => [MissingDependencyException::class];
    }

    #[DataProvider('containerException')]
    public function testExceptionIsInstanceOfContainerExceptionInterface(string $exception): void
    {
        $this->assertTrue(is_a($exception, ContainerExceptionInterface::class, true));
    }

    public function testContainerNotRegisteredExceptionForMiddlewareService(): void
    {
        $exception = ContainerNotRegisteredException::forMiddlewareService('foo');

        $this->assertSame(
            'Cannot marshal middleware by service name "foo"; no container registered',
            $exception->getMessage()
        );
    }
}
