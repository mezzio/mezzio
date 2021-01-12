<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest;

use Laminas\Stratigility\Middleware\CallableMiddlewareDecorator;
use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Exception;
use Mezzio\Middleware\LazyLoadingMiddleware;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Middleware\DispatchMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;

use function array_shift;
use function iterator_to_array;

class MiddlewareFactoryTest extends TestCase
{
    public function setUp() : void
    {
        $this->container = $this->prophesize(MiddlewareContainer::class);
        $this->factory = new MiddlewareFactory($this->container->reveal());
    }

    public function assertLazyLoadingMiddleware(string $expectedMiddlewareName, MiddlewareInterface $middleware) : void
    {
        $this->assertInstanceOf(LazyLoadingMiddleware::class, $middleware);
        $this->assertAttributeSame($this->container->reveal(), 'container', $middleware);
        $this->assertAttributeSame($expectedMiddlewareName, 'middlewareName', $middleware);
    }

    public function assertCallableMiddleware(callable $expectedCallable, MiddlewareInterface $middleware) : void
    {
        $this->assertInstanceOf(CallableMiddlewareDecorator::class, $middleware);
        $this->assertAttributeSame($expectedCallable, 'middleware', $middleware);
    }

    public function assertPipeline(array $expectedPipeline, MiddlewareInterface $middleware) : void
    {
        $this->assertInstanceOf(MiddlewarePipe::class, $middleware);
        $pipeline = $this->reflectPipeline($middleware);
        $this->assertSame($expectedPipeline, $pipeline);
    }

    public function reflectPipeline(MiddlewarePipe $pipeline) : array
    {
        $r = new ReflectionProperty($pipeline, 'pipeline');
        $r->setAccessible(true);
        return iterator_to_array($r->getValue($pipeline));
    }

    public function testCallableDecoratesCallableMiddleware() : void
    {
        $callable = function ($request, $handler) {
        };

        $middleware = $this->factory->callable($callable);
        $this->assertCallableMiddleware($callable, $middleware);
    }

    public function testLazyLoadingMiddlewareDecoratesMiddlewareServiceName() : void
    {
        $middleware = $this->factory->lazy('service');
        $this->assertLazyLoadingMiddleware('service', $middleware);
    }

    public function testPrepareReturnsMiddlewareImplementationsVerbatim() : void
    {
        $middleware = $this->prophesize(MiddlewareInterface::class)->reveal();
        $this->assertSame($middleware, $this->factory->prepare($middleware));
    }

    public function testPrepareDecoratesCallables() : void
    {
        $callable = function ($request, $handler) {
        };

        $middleware = $this->factory->prepare($callable);
        $this->assertInstanceOf(CallableMiddlewareDecorator::class, $middleware);
        $this->assertAttributeSame($callable, 'middleware', $middleware);
    }

    public function testPrepareDecoratesServiceNamesAsLazyLoadingMiddleware() : void
    {
        $middleware = $this->factory->prepare('service');
        $this->assertInstanceOf(LazyLoadingMiddleware::class, $middleware);
        $this->assertAttributeSame('service', 'middlewareName', $middleware);
        $this->assertAttributeSame($this->container->reveal(), 'container', $middleware);
    }

    public function testPrepareDecoratesArraysAsMiddlewarePipes() : void
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware3 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $middleware = $this->factory->prepare([$middleware1, $middleware2, $middleware3]);
        $this->assertPipeline([$middleware1, $middleware2, $middleware3], $middleware);
    }

    public function invalidMiddlewareTypes() : iterable
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

    /**
     * @dataProvider invalidMiddlewareTypes
     */
    public function testPrepareRaisesExceptionForTypesItDoesNotUnderstand($middleware) : void
    {
        $this->expectException(Exception\InvalidMiddlewareException::class);
        $this->factory->prepare($middleware);
    }

    public function testPipelineAcceptsMultipleArguments() : void
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware3 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $middleware = $this->factory->pipeline($middleware1, $middleware2, $middleware3);
        $this->assertPipeline([$middleware1, $middleware2, $middleware3], $middleware);
    }

    public function testPipelineAcceptsASingleArrayArgument() : void
    {
        $middleware1 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware2 = $this->prophesize(MiddlewareInterface::class)->reveal();
        $middleware3 = $this->prophesize(MiddlewareInterface::class)->reveal();

        $middleware = $this->factory->pipeline([$middleware1, $middleware2, $middleware3]);
        $this->assertPipeline([$middleware1, $middleware2, $middleware3], $middleware);
    }

    /**
     * @return iterable<
     *     string,
     *     array{0: string|callable|MiddlewareInterface, 1: string, 2: string|callable|MiddlewareInterface}
     * >
     */
    public function validPrepareTypes() : iterable
    {
        yield 'service' => ['service', 'assertLazyLoadingMiddleware', 'service'];

        $callable = function ($request, $handler) {
        };
        yield 'callable' => [$callable, 'assertCallableMiddleware', $callable];

        $middleware = new DispatchMiddleware();
        yield 'instance' => [$middleware, 'assertSame', $middleware];
    }

    /**
     * @dataProvider validPrepareTypes
     * @param string|callable|MiddlewareInterface $middleware
     * @param mixed $expected Expected type or value for use with assertion
     */
    public function testPipelineAllowsAnyTypeSupportedByPrepare(
        $middleware,
        string $assertion,
        $expected
    ) : void {
        $pipeline = $this->factory->pipeline($middleware);
        $this->assertInstanceOf(MiddlewarePipe::class, $pipeline);

        $r = new ReflectionProperty($pipeline, 'pipeline');
        $r->setAccessible(true);
        $values = iterator_to_array($r->getValue($pipeline));
        $received = array_shift($values);

        $this->{$assertion}($expected, $received);
    }

    public function testPipelineAllowsPipingArraysOfMiddlewareAndCastsThemToInternalPipelines() : void
    {
        $callable = function ($request, $handler) {
        };
        $middleware = new DispatchMiddleware();

        $internalPipeline = [$callable, $middleware];

        $pipeline = $this->factory->pipeline($internalPipeline);

        $this->assertInstanceOf(MiddlewarePipe::class, $pipeline);
        $received = $this->reflectPipeline($pipeline);
        $this->assertCount(2, $received);
        $this->assertCallableMiddleware($callable, $received[0]);
        $this->assertSame($middleware, $received[1]);
    }

    public function testPrepareDecoratesRequestHandlersAsMiddleware() : void
    {
        $handler = $this->prophesize(RequestHandlerInterface::class)->reveal();
        $middleware = $this->factory->prepare($handler);
        $this->assertInstanceOf(RequestHandlerMiddleware::class, $middleware);
        $this->assertAttributeSame($handler, 'handler', $middleware);
    }

    public function testHandlerDecoratesRequestHandlersAsMiddleware() : void
    {
        $handler = $this->prophesize(RequestHandlerInterface::class)->reveal();
        $middleware = $this->factory->handler($handler);
        $this->assertInstanceOf(RequestHandlerMiddleware::class, $middleware);
        $this->assertAttributeSame($handler, 'handler', $middleware);
    }
}
