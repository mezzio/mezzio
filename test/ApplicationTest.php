<?php

declare(strict_types=1);

namespace MezzioTest;

use Laminas\Diactoros\Response;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Laminas\Stratigility\Middleware\PathMiddlewareDecorator;
use Laminas\Stratigility\MiddlewarePipe;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Mezzio\Application;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TypeError;

use function sprintf;
use function strtoupper;

/** @psalm-import-type MiddlewareParam from MiddlewareFactoryInterface */
class ApplicationTest extends TestCase
{
    /** @var MiddlewareFactoryInterface&MockObject */
    private $factory;

    /** @var MiddlewarePipeInterface&MockObject */
    private $pipeline;

    /** @var RouteCollector&MockObject */
    private $routes;

    /** @var RequestHandlerRunnerInterface&MockObject */
    private $runner;

    private Application $app;

    public function setUp(): void
    {
        $this->factory  = $this->createMock(MiddlewareFactoryInterface::class);
        $this->pipeline = $this->createMock(MiddlewarePipeInterface::class);
        $this->routes   = $this->createMock(RouteCollector::class);
        $this->runner   = $this->createMock(RequestHandlerRunnerInterface::class);

        $this->app = new Application(
            $this->factory,
            $this->pipeline,
            $this->routes,
            $this->runner
        );
    }

    public function createMockMiddleware(): MiddlewareInterface
    {
        return $this->createMock(MiddlewareInterface::class);
    }

    public function testHandleProxiesToPipelineToHandle(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $this->pipeline->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        $this->assertSame($response, $this->app->handle($request));
    }

    public function testProcessProxiesToPipelineToProcess(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);

        $this->pipeline->expects(self::once())
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);

        $this->assertSame($response, $this->app->process($request, $handler));
    }

    public function testRunProxiesToRunner(): void
    {
        $this->runner->expects(self::once())->method('run');
        $this->app->run();
    }

    /** @return iterable<string, array{0: MiddlewareParam}> */
    public static function validMiddleware(): iterable
    {
        // @codingStandardsIgnoreStart
        yield 'string'   => ['service'];
        yield 'array'    => [['middleware', 'service']];
        yield 'callable' => [fn ( ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface => new Response()];
        yield 'instance' => [new MiddlewarePipe()];
        // @codingStandardsIgnoreEnd
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testPipeCanAcceptSingleMiddlewareArgument($middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();
        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $this->pipeline
            ->expects(self::once())
            ->method('pipe')
            ->with(self::identicalTo($preparedMiddleware));

        $this->app->pipe($middleware);
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testPipeCanAcceptAPathArgument($middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();
        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $this->pipeline
            ->expects(self::once())
            ->method('pipe')
            ->with(new PathMiddlewareDecorator('/foo', $preparedMiddleware));

        $this->app->pipe('/foo', $middleware);
    }

    public function testPipeNonSlashPathOnNonStringPipeProduceTypeError(): void
    {
        $middleware1 = static fn(ServerRequestInterface $req, RequestHandlerInterface $res): ResponseInterface
            => new Response();
        $middleware2 = $this->createMockMiddleware();

        $this->expectException(TypeError::class);
        $this->app->pipe($middleware1, $middleware2);
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testRouteAcceptsPathAndMiddlewareOnly($middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->createMock(Route::class);

        $this->routes->expects(self::once())
            ->method('route')
            ->with(
                '/foo',
                $preparedMiddleware,
                null,
                null
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->route('/foo', $middleware));
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testRouteAcceptsPathMiddlewareAndMethodsOnly($middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->createMock(Route::class);

        $this->routes->expects(self::once())
            ->method('route')
            ->with(
                '/foo',
                $preparedMiddleware,
                ['GET', 'POST'],
                null
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->route('/foo', $middleware, ['GET', 'POST']));
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testRouteAcceptsPathMiddlewareMethodsAndName($middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->createMock(Route::class);

        $this->routes->expects(self::once())
            ->method('route')
            ->with(
                '/foo',
                $preparedMiddleware,
                ['GET', 'POST'],
                'foo'
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->route('/foo', $middleware, ['GET', 'POST'], 'foo'));
    }

    /** @return iterable<string, array{0: string, 1: MiddlewareParam}> */
    public static function requestMethodsWithValidMiddleware(): iterable
    {
        foreach (['get', 'post', 'put', 'patch', 'delete'] as $method) {
            foreach (self::validMiddleware() as $key => $data) {
                $name = sprintf('%s-%s', $method, $key);
                yield $name => [$method, $data[0]];
            }
        }
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('requestMethodsWithValidMiddleware')]
    public function testSpecificRouteMethodsCanAcceptOnlyPathAndMiddleware(string $method, $middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->createMock(Route::class);

        $this->routes->expects(self::once())
            ->method('route')
            ->with(
                '/foo',
                $preparedMiddleware,
                [strtoupper($method)],
                null
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->{$method}('/foo', $middleware));
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('requestMethodsWithValidMiddleware')]
    public function testSpecificRouteMethodsCanAcceptPathMiddlewareAndName(string $method, $middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->createMock(Route::class);

        $this->routes->expects(self::once())
            ->method('route')
            ->with(
                '/foo',
                $preparedMiddleware,
                [strtoupper($method)],
                'foo'
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->{$method}('/foo', $middleware, 'foo'));
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testAnyMethodPassesNullForMethodWhenNoNamePresent($middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->createMock(Route::class);

        $this->routes->expects(self::once())
            ->method('route')
            ->with(
                '/foo',
                $preparedMiddleware,
                null,
                null
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->any('/foo', $middleware));
    }

    /**
     * @param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testAnyMethodPassesNullForMethodWhenAllArgumentsPresent($middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = $this->createMock(Route::class);

        $this->routes->expects(self::once())
            ->method('route')
            ->with(
                '/foo',
                $preparedMiddleware,
                null,
                'foo'
            )
            ->willReturn($route);

        $this->assertSame($route, $this->app->any('/foo', $middleware, 'foo'));
    }

    public function testGetRoutesProxiesToRouteCollector(): void
    {
        $route = $this->createMock(Route::class);
        $this->routes->method('getRoutes')->willReturn([$route]);

        $this->assertSame([$route], $this->app->getRoutes());
    }
}
