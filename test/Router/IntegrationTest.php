<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Application;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\AuraRouter;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_pop;
use function sprintf;

use const PHP_VERSION_ID;

class IntegrationTest extends TestCase
{
    /** @var Response */
    private $response;

    /** @var callable */
    private $responseFactory;

    public function setUp(): void
    {
        $this->response        = new Response();
        $this->responseFactory = function (): Response {
            return $this->response;
        };
    }

    public function getApplication(): Application
    {
        return $this->createApplicationFromRouter($this->createMock(RouterInterface::class));
    }

    public function createApplicationFromRouter(RouterInterface $router): Application
    {
        $container = new MiddlewareContainer($this->createMock(ContainerInterface::class));
        $factory   = new MiddlewareFactory($container);
        $pipeline  = new MiddlewarePipe();
        $collector = new RouteCollector($router);
        $runner    = $this->createMock(RequestHandlerRunnerInterface::class);
        return new Application(
            $factory,
            $pipeline,
            $collector,
            $runner
        );
    }

    /**
     * Get the router adapters to test
     *
     * @psalm-return iterable<string, array{class-string<RouterInterface>}>
     */
    public function routerAdapters(): iterable
    {
        if (PHP_VERSION_ID < 80100) {
            /**
             * Excluded from PHP 8.1 onwards since the package seems to be abandoned.
             */
            yield 'aura' => [AuraRouter::class];
        }

        yield 'fast-route' => [FastRouteRouter::class];
        yield 'laminas'    => [LaminasRouter::class];
    }

    /**
     * Create an Application object with 2 routes, a GET and a POST
     * using Application::get() and Application::post()
     *
     * @psalm-param class-string<RouterInterface> $adapter
     */
    private function createApplicationWithGetPost(
        string $adapter,
        ?string $getName = null,
        ?string $postName = null
    ): Application {
        $router = new $adapter();
        $app    = $this->createApplicationFromRouter($router);
        $app->pipe(new RouteMiddleware($router));
        $app->pipe(new MethodNotAllowedMiddleware($this->responseFactory));

        $app->get('/foo', function ($req, $handler) {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware GET');
            return $this->response->withBody($stream);
        }, $getName);
        $app->post('/foo', function ($req, $handler) {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware POST');
            return $this->response->withBody($stream);
        }, $postName);

        return $app;
    }

    /**
     * Create an Application object with 2 routes, a GET and a POST
     * using Application::route()
     *
     * @psalm-param class-string<RouterInterface> $adapter
     */
    private function createApplicationWithRouteGetPost(
        string $adapter,
        ?string $getName = null,
        ?string $postName = null
    ): Application {
        $router = new $adapter();
        $app    = $this->createApplicationFromRouter($router);
        $app->pipe(new RouteMiddleware($router));
        $app->pipe(new MethodNotAllowedMiddleware($this->responseFactory));

        $app->route('/foo', function ($req, $handler) {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware GET');
            return $this->response->withBody($stream);
        }, ['GET'], $getName);
        $app->route('/foo', function ($req, $handler) {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware POST');
            return $this->response->withBody($stream);
        }, ['POST'], $postName);

        return $app;
    }

    /**
     * @dataProvider routerAdapters
     * @psalm-param class-string<RouterInterface> $adapter
     */
    public function testRoutingDoesNotMatchMethod(string $adapter): void
    {
        $app     = $this->createApplicationWithGetPost($adapter);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => 'DELETE'], [], '/foo', 'DELETE');
        $result  = $app->process($request, $handler);

        $this->assertSame(StatusCode::STATUS_METHOD_NOT_ALLOWED, $result->getStatusCode());
        $headers = $result->getHeaders();
        $this->assertSame(['GET,POST'], $headers['Allow']);
    }

    /**
     * @see https://github.com/zendframework/zend-expressive/issues/40
     *
     * @group 40
     * @dataProvider routerAdapters
     * @psalm-param class-string<RouterInterface> $adapter
     */
    public function testRoutingWithSamePathWithoutName(string $adapter): void
    {
        $app = $this->createApplicationWithGetPost($adapter);
        $app->pipe(new DispatchMiddleware());

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => 'GET'], [], '/foo', 'GET');
        $result  = $app->process($request, $handler);

        $this->assertEquals('Middleware GET', (string) $result->getBody());

        $request = new ServerRequest(['REQUEST_METHOD' => 'POST'], [], '/foo', 'POST');
        $result  = $app->process($request, $handler);

        $this->assertEquals('Middleware POST', (string) $result->getBody());
    }

    /**
     * @see https://github.com/zendframework/zend-expressive/issues/40
     *
     * @group 40
     * @dataProvider routerAdapters
     * @psalm-param class-string<RouterInterface> $adapter
     */
    public function testRoutingWithSamePathWithName(string $adapter): void
    {
        $app = $this->createApplicationWithGetPost($adapter, 'foo-get', 'foo-post');
        $app->pipe(new DispatchMiddleware());

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => 'GET'], [], '/foo', 'GET');
        $result  = $app->process($request, $handler);

        $this->assertEquals('Middleware GET', (string) $result->getBody());

        $request = new ServerRequest(['REQUEST_METHOD' => 'POST'], [], '/foo', 'POST');
        $result  = $app->process($request, $handler);

        $this->assertEquals('Middleware POST', (string) $result->getBody());
    }

    /**
     * @see https://github.com/zendframework/zend-expressive/issues/40
     *
     * @group 40
     * @dataProvider routerAdapters
     * @psalm-param class-string<RouterInterface> $adapter
     */
    public function testRoutingWithSamePathWithRouteWithoutName(string $adapter): void
    {
        $app = $this->createApplicationWithRouteGetPost($adapter);
        $app->pipe(new DispatchMiddleware());

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => 'GET'], [], '/foo', 'GET');
        $result  = $app->process($request, $handler);

        $this->assertEquals('Middleware GET', (string) $result->getBody());

        $request = new ServerRequest(['REQUEST_METHOD' => 'POST'], [], '/foo', 'POST');
        $result  = $app->process($request, $handler);

        $this->assertEquals('Middleware POST', (string) $result->getBody());
    }

    /**
     * @see https://github.com/zendframework/zend-expressive/issues/40
     *
     * @dataProvider routerAdapters
     * @psalm-param class-string<RouterInterface> $adapter
     */
    public function testRoutingWithSamePathWithRouteWithName(string $adapter): void
    {
        $app = $this->createApplicationWithRouteGetPost($adapter, 'foo-get', 'foo-post');
        $app->pipe(new DispatchMiddleware());

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => 'GET'], [], '/foo', 'GET');
        $result  = $app->process($request, $handler);

        $this->assertEquals('Middleware GET', (string) $result->getBody());

        $request = new ServerRequest(['REQUEST_METHOD' => 'POST'], [], '/foo', 'POST');
        $result  = $app->process($request, $handler);

        $this->assertEquals('Middleware POST', (string) $result->getBody());
    }

    /**
     * @see https://github.com/zendframework/zend-expressive/issues/40
     *
     * @group 40
     * @dataProvider routerAdapters
     * @psalm-param class-string<RouterInterface> $adapter
     */
    public function testRoutingWithSamePathWithRouteWithMultipleMethods(string $adapter): void
    {
        $router = new $adapter();
        $app    = $this->createApplicationFromRouter($router);
        $app->pipe(new RouteMiddleware($router));
        $app->pipe(new MethodNotAllowedMiddleware($this->responseFactory));
        $app->pipe(new DispatchMiddleware());

        $response = clone $this->response;
        $app->route('/foo', function ($req, $handler) use ($response): ResponseInterface {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware GET, POST');
            return $response->withBody($stream);
        }, ['GET', 'POST']);

        $deleteResponse = clone $this->response;
        $app->route('/foo', function ($req, $handler) use ($deleteResponse): ResponseInterface {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware DELETE');
            return $deleteResponse->withBody($stream);
        }, ['DELETE']);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => 'GET'], [], '/foo', 'GET');
        $result  = $app->process($request, $handler);
        $this->assertEquals('Middleware GET, POST', (string) $result->getBody());

        $request = new ServerRequest(['REQUEST_METHOD' => 'POST'], [], '/foo', 'POST');
        $result  = $app->process($request, $handler);
        $this->assertEquals('Middleware GET, POST', (string) $result->getBody());

        $request = new ServerRequest(['REQUEST_METHOD' => 'DELETE'], [], '/foo', 'DELETE');
        $result  = $app->process($request, $handler);
        $this->assertEquals('Middleware DELETE', (string) $result->getBody());
    }

    /**
     * @psalm-return iterable<string, array{
     *     0: class-string<RouterInterface>,
     *     1: RequestMethod::METHOD_*
     * }>
     */
    public function routerAdaptersForHttpMethods(): iterable
    {
        $allMethods = [
            RequestMethod::METHOD_GET,
            RequestMethod::METHOD_POST,
            RequestMethod::METHOD_PUT,
            RequestMethod::METHOD_DELETE,
            RequestMethod::METHOD_PATCH,
            RequestMethod::METHOD_HEAD,
            RequestMethod::METHOD_OPTIONS,
        ];
        foreach ($this->routerAdapters() as $adapterName => $adapter) {
            $adapter = array_pop($adapter);
            foreach ($allMethods as $method) {
                $name = sprintf('%s-%s', $adapterName, $method);
                yield $name => [$adapter, $method];
            }
        }
    }

    /**
     * @dataProvider routerAdaptersForHttpMethods
     * @psalm-param class-string<RouterInterface> $adapter
     * @psalm-param RequestMethod::METHOD_* $method
     */
    public function testMatchWithAllHttpMethods(string $adapter, string $method): void
    {
        $router = new $adapter();
        $app    = $this->createApplicationFromRouter($router);
        $app->pipe(new RouteMiddleware($router));
        $app->pipe(new MethodNotAllowedMiddleware($this->responseFactory));
        $app->pipe(new DispatchMiddleware());

        // Add a route with Mezzio\Router\Route::HTTP_METHOD_ANY
        $response = clone $this->response;
        $app->route('/foo', function ($req, $handler) use ($response): ResponseInterface {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware');
            return $response->withBody($stream);
        });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => $method], [], '/foo', $method);
        $result  = $app->process($request, $handler);
        $this->assertEquals('Middleware', (string) $result->getBody());
    }

    /**
     * @psalm-return iterable<array{
     *     0: class-string<RouterInterface>,
     *     1: RequestMethod::METHOD_*
     * }>
     */
    public function allowedMethod(): iterable
    {
        if (PHP_VERSION_ID < 80100) {
            /**
             * Excluded from PHP 8.1 onwards since the package seems to be abandoned.
             */
            yield 'aura-head'    => [AuraRouter::class, RequestMethod::METHOD_HEAD];
            yield 'aura-options' => [AuraRouter::class, RequestMethod::METHOD_OPTIONS];
        }

        yield 'fast-route-head'    => [FastRouteRouter::class, RequestMethod::METHOD_HEAD];
        yield 'fast-route-options' => [FastRouteRouter::class, RequestMethod::METHOD_OPTIONS];
        yield 'laminas-head'       => [LaminasRouter::class, RequestMethod::METHOD_HEAD];
        yield 'laminas-options'    => [LaminasRouter::class, RequestMethod::METHOD_OPTIONS];
    }

    /**
     * @dataProvider allowedMethod
     * @psalm-param class-string<RouterInterface> $adapter
     * @psalm-param RequestMethod::METHOD_* $method
     */
    public function testAllowedMethodsWhenOnlyPutMethodSet(string $adapter, string $method): void
    {
        $router = new $adapter();
        $app    = $this->createApplicationFromRouter($router);
        $app->pipe(new RouteMiddleware($router));
        $app->pipe(new ImplicitHeadMiddleware($router, function () {
        }));
        $app->pipe(new ImplicitOptionsMiddleware($this->responseFactory));
        $app->pipe(new MethodNotAllowedMiddleware($this->responseFactory));
        $app->pipe(new DispatchMiddleware());

        // Add a PUT route
        $app->put('/foo', function ($req, $res, $next): ResponseInterface {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware');
            return $res->withBody($stream);
        });

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = new ServerRequest(['REQUEST_METHOD' => $method], [], '/foo', $method);
        $result  = $app->process($request, $handler);

        if ($method === RequestMethod::METHOD_OPTIONS) {
            $this->assertSame(StatusCode::STATUS_OK, $result->getStatusCode());
        } else {
            $this->assertSame(StatusCode::STATUS_METHOD_NOT_ALLOWED, $result->getStatusCode());
        }
        $this->assertSame('', (string) $result->getBody());
    }

    /**
     * @group 74
     * @dataProvider routerAdapters
     */
    public function testWithOnlyRootPathRouteDefinedRoutingToSubPathsShouldDelegate(string $adapter): void
    {
        $router = new $adapter();
        $app    = $this->createApplicationFromRouter($router);
        $app->pipe(new RouteMiddleware($router));

        $response = clone $this->response;
        $app->route('/', function ($req, $handler) use ($response) {
            $stream = new Stream('php://temp', 'w+');
            $stream->write('Middleware');
            return $response->withBody($stream);
        }, ['GET']);

        $expected = $this->response->withStatus(StatusCode::STATUS_NOT_FOUND);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $handler->method('handle')->willReturn($expected);

        $request = new ServerRequest(['REQUEST_METHOD' => 'GET'], [], '/foo', 'GET');
        $result  = $app->process($request, $handler);
        $this->assertSame($expected, $result);
    }
}
