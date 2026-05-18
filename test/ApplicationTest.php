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
use Mezzio\Router\RouteCollectorInterface;
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
final class ApplicationTest extends TestCase
{
    private MiddlewareFactoryInterface&MockObject $factory;

    private MiddlewarePipeInterface&MockObject $pipeline;

    private RouteCollectorInterface&MockObject $routes;

    private RequestHandlerRunnerInterface&MockObject $runner;

    private Application $app;

    public function setUp(): void
    {
        $this->factory  = $this->createMock(MiddlewareFactoryInterface::class);
        $this->pipeline = $this->createMock(MiddlewarePipeInterface::class);
        $this->routes   = $this->createMock(RouteCollectorInterface::class);
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
     * @psalm-param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testPipeCanAcceptSingleMiddlewareArgument(mixed $middleware): void
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
     * @psalm-param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testPipeCanAcceptAPathArgument(mixed $middleware): void
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
     * @psalm-param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testRouteAcceptsPathAndMiddlewareOnly(mixed $middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = new Route('/foo', $preparedMiddleware);

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
     * @psalm-param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testRouteAcceptsPathMiddlewareAndMethodsOnly(mixed $middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = new Route('/foo', $preparedMiddleware);

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
     * @psalm-param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testRouteAcceptsPathMiddlewareMethodsAndName(mixed $middleware): void
    {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = new Route('/foo', $preparedMiddleware);

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

        $route = new Route('/foo', $preparedMiddleware);

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

        $route = new Route('/foo', $preparedMiddleware);

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
     * @psalm-param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testAnyMethodPassesNullForMethodWhenNoNamePresent(
        mixed $middleware,
    ): void {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = new Route('/foo', $preparedMiddleware);

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
     * @psalm-param MiddlewareParam $middleware
     */
    #[DataProvider('validMiddleware')]
    public function testAnyMethodPassesNullForMethodWhenAllArgumentsPresent(
        mixed $middleware,
    ): void {
        $preparedMiddleware = $this->createMockMiddleware();

        $this->factory->expects(self::once())
            ->method('prepare')
            ->with($middleware)
            ->willReturn($preparedMiddleware);

        $route = new Route('/foo', $preparedMiddleware);

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
        $route = new Route('/foo', $this->createMockMiddleware());
        $this->routes->method('getRoutes')->willReturn([$route]);

        $this->assertSame([$route], $this->app->getRoutes());
    }
}
