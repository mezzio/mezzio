<?php

declare(strict_types=1);

namespace MezzioTest;

use Closure;
use Laminas\Diactoros\Response;
use Laminas\Stratigility\Middleware\CallableMiddlewareDecorator;
use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Exception;
use Mezzio\Middleware\LazyLoadingMiddleware;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Middleware\DispatchMiddleware;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;

use function array_shift;
use function iterator_to_array;

/** @psalm-import-type MiddlewareParam from MiddlewareFactoryInterface */
class MiddlewareFactoryTest extends TestCase
{
    /** @var MiddlewareContainer&MockObject */
    private $container;

    private MiddlewareFactory $factory;

    public function setUp(): void
    {
        $this->container = $this->createMock(MiddlewareContainer::class);
        $this->factory   = new MiddlewareFactory($this->container);
    }

    /** @return Closure(ServerRequestInterface, RequestHandlerInterface): ResponseInterface */
    private static function validCallable(): Closure
    {
        return function (
            ServerRequestInterface $request,
            RequestHandlerInterface $handler
        ): ResponseInterface {
            return new Response();
        };
    }

    public function assertLazyLoadingMiddleware(string $expectedMiddlewareName, MiddlewareInterface $middleware): void
    {
        self::assertEquals(new LazyLoadingMiddleware($this->container, $expectedMiddlewareName), $middleware);
    }

    public function assertCallableMiddleware(callable $expectedCallable, MiddlewareInterface $middleware): void
    {
        self::assertEquals(new CallableMiddlewareDecorator($expectedCallable), $middleware);
    }

    public function assertPipeline(array $expectedPipeline, MiddlewareInterface $middleware): void
    {
        $this->assertInstanceOf(MiddlewarePipe::class, $middleware);
        $pipeline = $this->reflectPipeline($middleware);
        $this->assertSame($expectedPipeline, $pipeline);
    }

    public function reflectPipeline(MiddlewarePipe $pipeline): array
    {
        $r = new ReflectionProperty($pipeline, 'pipeline');
        $r->setAccessible(true);
        return iterator_to_array($r->getValue($pipeline));
    }

    public function testCallableDecoratesCallableMiddleware(): void
    {
        $callable = static function ($request, $handler): void {
        };

        $middleware = $this->factory->callable($callable);
        $this->assertCallableMiddleware($callable, $middleware);
    }

    public function testLazyLoadingMiddlewareDecoratesMiddlewareServiceName(): void
    {
        $middleware = $this->factory->lazy('service');
        $this->assertLazyLoadingMiddleware('service', $middleware);
    }

    public function testPrepareReturnsMiddlewareImplementationsVerbatim(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);
        $this->assertSame($middleware, $this->factory->prepare($middleware));
    }

    public function testPrepareDecoratesCallables(): void
    {
        $middleware = $this->factory->prepare(self::validCallable());

        self::assertEquals(new CallableMiddlewareDecorator(self::validCallable()), $middleware);
    }

    public function testPrepareDecoratesServiceNamesAsLazyLoadingMiddleware(): void
    {
        $middleware = $this->factory->prepare('service');

        self::assertEquals(new LazyLoadingMiddleware($this->container, 'service'), $middleware);
    }

    public function testPrepareDecoratesArraysAsMiddlewarePipes(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware3 = $this->createMock(MiddlewareInterface::class);

        $middleware = $this->factory->prepare([$middleware1, $middleware2, $middleware3]);
        $this->assertPipeline([$middleware1, $middleware2, $middleware3], $middleware);
    }

    /** @return iterable<string, array{0: mixed}> */
    public static function invalidMiddlewareTypes(): iterable
    {
        yield 'null' => [null];
        yield 'false' => [false];
        yield 'true' => [true];
        yield 'zero' => [0];
        yield 'int' => [1];
        yield 'zero-float' => [0.0];
        yield 'float' => [1.1];
        yield 'object' => [(object) ['foo' => 'bar']];
    }

    #[DataProvider('invalidMiddlewareTypes')]
    public function testPrepareRaisesExceptionForTypesItDoesNotUnderstand(mixed $middleware): void
    {
        $this->expectException(Exception\InvalidMiddlewareException::class);
        /** @psalm-suppress MixedArgument */
        $this->factory->prepare($middleware);
    }

    public function testPipelineAcceptsMultipleArguments(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware3 = $this->createMock(MiddlewareInterface::class);

        $middleware = $this->factory->pipeline($middleware1, $middleware2, $middleware3);
        $this->assertPipeline([$middleware1, $middleware2, $middleware3], $middleware);
    }

    public function testPipelineAcceptsASingleArrayArgument(): void
    {
        $middleware1 = $this->createMock(MiddlewareInterface::class);
        $middleware2 = $this->createMock(MiddlewareInterface::class);
        $middleware3 = $this->createMock(MiddlewareInterface::class);

        $middleware = $this->factory->pipeline([$middleware1, $middleware2, $middleware3]);
        $this->assertPipeline([$middleware1, $middleware2, $middleware3], $middleware);
    }

    /**
     * @return iterable<
     *     string,
     *     array{0: MiddlewareParam, 1: string, 2: MiddlewareParam}
     * >
     */
    public static function validPrepareTypes(): iterable
    {
        yield 'service' => ['service', 'assertLazyLoadingMiddleware', 'service'];

        yield 'callable' => [self::validCallable(), 'assertCallableMiddleware', self::validCallable()];

        $middleware = new DispatchMiddleware();
        yield 'instance' => [$middleware, 'assertSame', $middleware];
    }

    /**
     * @param MiddlewareParam $middleware
     * @param mixed $expected Expected type or value for use with assertion
     */
    #[DataProvider('validPrepareTypes')]
    public function testPipelineAllowsAnyTypeSupportedByPrepare(
        $middleware,
        string $assertion,
        mixed $expected
    ): void {
        $pipeline = $this->factory->pipeline($middleware);
        $this->assertInstanceOf(MiddlewarePipe::class, $pipeline);

        $r = new ReflectionProperty($pipeline, 'pipeline');
        $r->setAccessible(true);
        $values   = iterator_to_array($r->getValue($pipeline));
        $received = array_shift($values);

        $this->{$assertion}($expected, $received);
    }

    public function testPipelineAllowsPipingArraysOfMiddlewareAndCastsThemToInternalPipelines(): void
    {
        $middleware = new DispatchMiddleware();

        $internalPipeline = [self::validCallable(), $middleware];

        $pipeline = $this->factory->pipeline($internalPipeline);

        $this->assertInstanceOf(MiddlewarePipe::class, $pipeline);
        $received = $this->reflectPipeline($pipeline);
        $this->assertCount(2, $received);
        $this->assertCallableMiddleware(self::validCallable(), $received[0]);
        $this->assertSame($middleware, $received[1]);
    }

    public function testPrepareDecoratesRequestHandlersAsMiddleware(): void
    {
        $handler    = $this->createMock(RequestHandlerInterface::class);
        $middleware = $this->factory->prepare($handler);

        self::assertEquals(new RequestHandlerMiddleware($handler), $middleware);
    }

    public function testHandlerDecoratesRequestHandlersAsMiddleware(): void
    {
        $handler    = $this->createMock(RequestHandlerInterface::class);
        $middleware = $this->factory->handler($handler);

        self::assertEquals(new RequestHandlerMiddleware($handler), $middleware);
    }
}
