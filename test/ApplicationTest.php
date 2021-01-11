<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\PathMiddlewareDecorator;
use Laminas\Stratigility\MiddlewarePipe;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Mezzio\Application;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TypeError;

use function array_unshift;
use function sprintf;
use function strtoupper;

class ApplicationTest extends TestCase
{
    public function setUp(): void
    {
        $this->factory = $this->prophesize(MiddlewareFactory::class);
        $this->pipeline = $this->prophesize(MiddlewarePipeInterface::class);
        $this->routes = $this->prophesize(RouteCollector::class);
        $this->runner = $this->prophesize(RequestHandlerRunner::class);

        $this->app = new Application(
            $this->factory->reveal(),
            $this->pipeline->reveal(),
            $this->routes->reveal(),
            $this->runner->reveal()
        );
    }

    public function createMockMiddleware()
    {
        return $this->prophesize(MiddlewareInterface::class)->reveal();
    }

    public function testHandleProxiesToPipelineToHandle()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $this->pipeline->handle($request)->willReturn($response);

        $this->assertSame($response, $this->app->handle($request));
    }

    public function testProcessProxiesToPipelineToProcess()
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $handler = $this->prophesize(RequestHandlerInterface::class)->reveal();

        $this->pipeline->process($request, $handler)->willReturn($response);

        $this->assertSame($response, $this->app->process($request, $handler));
    }

    public function testRunProxiesToRunner()
    {
        $this->runner->run(null)->shouldBeCalled();
        $this->assertNull($this->app->run());
    }

    public function validMiddleware() : iterable
    {
        // @codingStandardsIgnoreStart
        yield 'string'   => ['service'];
        yield 'array'    => [['middleware', 'service']];
        yield 'callable' => [function ($request, $response) {}];
        yield 'instance' => [new MiddlewarePipe()];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @dataProvider validMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testPipeCanAcceptSingleMiddlewareArgument($middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();
        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $this->pipeline
            ->pipe(Argument::that(function ($test) use ($preparedMiddleware) {
                Assert::assertSame($preparedMiddleware, $test);
                return $test;
            }))
            ->shouldBeCalled();

        $this->assertNull($this->app->pipe($middleware));
    }

    /**
     * @dataProvider validMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testPipeCanAcceptAPathArgument($middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();
        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $this->pipeline
            ->pipe(Argument::that(function ($test) use ($preparedMiddleware) {
                Assert::assertInstanceOf(PathMiddlewareDecorator::class, $test);
                Assert::assertAttributeSame('/foo', 'prefix', $test);
                Assert::assertAttributeSame($preparedMiddleware, 'middleware', $test);
                return $test;
            }))
            ->shouldBeCalled();

        $this->assertNull($this->app->pipe('/foo', $middleware));
    }

    public function testPipeNonSlashPathOnNonStringPipeProduceTypeError()
    {
        $middleware1 = function ($request, $response) {
            return $response;
        };
        $middleware2 = $this->createMockMiddleware();

        $this->expectException(TypeError::class);
        $this->app->pipe($middleware1, $middleware2);
    }

    /**
     * @dataProvider validMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testRouteAcceptsPathAndMiddlewareOnly($middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->prophesize(Route::class)->reveal();

        $this->routes
            ->route(
                '/foo',
                $preparedMiddleware,
                null,
                null
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->route('/foo', $middleware));
    }

    /**
     * @dataProvider validMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testRouteAcceptsPathMiddlewareAndMethodsOnly($middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->prophesize(Route::class)->reveal();

        $this->routes
            ->route(
                '/foo',
                $preparedMiddleware,
                ['GET', 'POST'],
                null
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->route('/foo', $middleware, ['GET', 'POST']));
    }

    /**
     * @dataProvider validMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testRouteAcceptsPathMiddlewareMethodsAndName($middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->prophesize(Route::class)->reveal();

        $this->routes
            ->route(
                '/foo',
                $preparedMiddleware,
                ['GET', 'POST'],
                'foo'
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->route('/foo', $middleware, ['GET', 'POST'], 'foo'));
    }

    public function requestMethodsWithValidMiddleware() : iterable
    {
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            foreach ($this->validMiddleware() as $key => $data) {
                array_unshift($data, $method);
                $name = sprintf('%s-%s', $method, $key);
                yield $name => $data;
            }
        }
    }

    /**
     * @dataProvider requestMethodsWithValidMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testSpecificRouteMethodsCanAcceptOnlyPathAndMiddleware(string $method, $middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->prophesize(Route::class)->reveal();

        $this->routes
            ->route(
                '/foo',
                $preparedMiddleware,
                [strtoupper($method)],
                null
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->{$method}('/foo', $middleware));
    }

    /**
     * @dataProvider requestMethodsWithValidMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testSpecificRouteMethodsCanAcceptPathMiddlewareAndName(string $method, $middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->prophesize(Route::class)->reveal();

        $this->routes
            ->route(
                '/foo',
                $preparedMiddleware,
                [strtoupper($method)],
                'foo'
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->{$method}('/foo', $middleware, 'foo'));
    }

    /**
     * @dataProvider validMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testAnyMethodPassesNullForMethodWhenNoNamePresent($middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->prophesize(Route::class)->reveal();

        $this->routes
            ->route(
                '/foo',
                $preparedMiddleware,
                null,
                null
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->any('/foo', $middleware));
    }

    /**
     * @dataProvider validMiddleware
     * @param string|array|callable|MiddlewareInterface $middleware
     */
    public function testAnyMethodPassesNullForMethodWhenAllArgumentsPresent($middleware)
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory
            ->prepare($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->prophesize(Route::class)->reveal();

        $this->routes
            ->route(
                '/foo',
                $preparedMiddleware,
                null,
                'foo'
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->any('/foo', $middleware, 'foo'));
    }

    public function testGetRoutesProxiesToRouteCollector()
    {
        $route = $this->prophesize(Route::class)->reveal();
        $this->routes->getRoutes()->willReturn([$route]);

        $this->assertSame([$route], $this->app->getRoutes());
    }
}
