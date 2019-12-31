<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Router;

use Laminas\Diactoros\ServerRequest;
use Laminas\Http\Request as LaminasRequest;
use Laminas\Mvc\Router\Http\TreeRouteStack;
use Laminas\Mvc\Router\RouteMatch;
use Mezzio\Router\LaminasRouter;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;

class LaminasRouterTest extends TestCase
{
    public function setUp()
    {
        $this->laminasRouter = $this->prophesize(TreeRouteStack::class);
    }

    public function getRouter()
    {
        return new LaminasRouter($this->laminasRouter->reveal());
    }

    public function testWillLazyInstantiateALaminasTreeRouteStackIfNoneIsProvidedToConstructor()
    {
        $router = new LaminasRouter();
        $this->assertAttributeInstanceOf(TreeRouteStack::class, 'laminasRouter', $router);
    }

    public function createRequestProphecy()
    {
        $request = $this->prophesize('Psr\Http\Message\ServerRequestInterface');

        $uri = $this->prophesize('Psr\Http\Message\UriInterface');
        $uri->getPath()->willReturn('/foo');
        $uri->__toString()->willReturn('http://www.example.com/foo');

        $request->getMethod()->willReturn('GET');
        $request->getUri()->willReturn($uri);
        $request->getHeaders()->willReturn([]);
        $request->getCookieParams()->willReturn([]);
        $request->getQueryParams()->willReturn([]);
        $request->getServerParams()->willReturn([]);

        return $request;
    }

    public function testAddingRouteAggregatesInRouter()
    {
        $route = new Route('/foo', 'foo', ['GET']);
        $router = $this->getRouter();
        $router->addRoute($route);
        $this->assertAttributeContains($route, 'routesToInject', $router);
    }

    /**
     * @depends testAddingRouteAggregatesInRouter
     */
    public function testMatchingInjectsRoutesInRouter()
    {
        $route = new Route('/foo', 'foo', ['GET']);

        $this->laminasRouter->addRoute('/foo^GET', [
            'type' => 'segment',
            'options' => [
                'route' => '/foo',
            ],
            'may_terminate' => false,
            'child_routes' => [
                'GET' => [
                    'type' => 'method',
                    'options' => [
                        'verb' => 'GET',
                        'defaults' => [
                            'middleware' => 'foo',
                        ],
                    ],
                ],
                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                    'type'     => 'regex',
                    'priority' => -1,
                    'options'  => [
                        'regex' => '/*$',
                        'defaults' => [
                            LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo',
                        ],
                        'spec' => '',
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $router = $this->getRouter();
        $router->addRoute($route);

        $request = $this->createRequestProphecy();
        $this->laminasRouter->match(Argument::type(LaminasRequest::class))->willReturn(null);

        $router->match($request->reveal());
    }

    /**
     * @depends testAddingRouteAggregatesInRouter
     */
    public function testGeneratingUriInjectsRoutesInRouter()
    {
        $route = new Route('/foo', 'foo', ['GET']);

        $this->laminasRouter->addRoute('/foo^GET', [
            'type' => 'segment',
            'options' => [
                'route' => '/foo',
            ],
            'may_terminate' => false,
            'child_routes' => [
                'GET' => [
                    'type' => 'method',
                    'options' => [
                        'verb' => 'GET',
                        'defaults' => [
                            'middleware' => 'foo',
                        ],
                    ],
                ],
                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                    'type'     => 'regex',
                    'priority' => -1,
                    'options'  => [
                        'regex' => '/*$',
                        'defaults' => [
                            LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo',
                        ],
                        'spec' => '',
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $this->laminasRouter->hasRoute('foo')->willReturn(true);
        $this->laminasRouter->assemble(
            [],
            [
                'name' => 'foo',
                'only_return_path' => true,
            ]
        )->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);

        $this->assertEquals('/foo', $router->generateUri('foo'));
    }

    public function testCanSpecifyRouteOptions()
    {
        $route = new Route('/foo/:id', 'foo', ['GET']);
        $route->setOptions([
            'constraints' => [
                'id' => '\d+',
            ],
            'defaults' => [
                'bar' => 'baz',
            ],
        ]);

        $this->laminasRouter->addRoute('/foo/:id^GET', [
            'type' => 'segment',
            'options' => [
                'route' => '/foo/:id',
                'constraints' => [
                    'id' => '\d+',
                ],
                'defaults' => [
                    'bar' => 'baz'
                ],
            ],
            'may_terminate' => false,
            'child_routes' => [
                'GET' => [
                    'type' => 'method',
                    'options' => [
                        'verb' => 'GET',
                        'defaults' => [
                            'middleware' => 'foo',
                        ],
                    ],
                ],
                LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => [
                    'type'     => 'regex',
                    'priority' => -1,
                    'options'  => [
                        'regex' => '/*$',
                        'defaults' => [
                            LaminasRouter::METHOD_NOT_ALLOWED_ROUTE => '/foo/:id',
                        ],
                        'spec' => '',
                    ],
                ],
            ],
        ])->shouldBeCalled();

        $this->laminasRouter->hasRoute('foo')->willReturn(true);
        $this->laminasRouter->assemble(
            [],
            [
                'name' => 'foo',
                'only_return_path' => true,
            ]
        )->willReturn('/foo');

        $router = $this->getRouter();
        $router->addRoute($route);
        $router->generateUri('foo');
    }

    public function routeResults()
    {
        $successRoute = new Route('/foo', 'bar');
        return [
            'success' => [
                new Route('/foo', 'bar'),
                RouteResult::fromRouteMatch('/foo', 'bar'),
            ],
            'failure' => [
                new Route('/foo', 'bar'),
                RouteResult::fromRouteFailure(),
            ],
        ];
    }

    public function testMatch()
    {
        $middleware = function ($req, $res, $next) {
            return $res;
        };

        $route = new Route('/foo', $middleware, ['GET']);
        $laminasRouter = new LaminasRouter();
        $laminasRouter->addRoute($route);

        $request = new ServerRequest([ 'REQUEST_METHOD' => 'GET' ], [], '/foo', 'GET');

        $result = $laminasRouter->match($request);
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertEquals('/foo^GET', $result->getMatchedRouteName());
        $this->assertEquals($middleware, $result->getMatchedMiddleware());
    }

    /**
     * @group match
     */
    public function testSuccessfulMatchIsPossible()
    {
        $routeMatch = $this->prophesize(RouteMatch::class);
        $routeMatch->getMatchedRouteName()->willReturn('/foo');
        $routeMatch->getParams()->willReturn([
            'middleware' => 'bar',
        ]);

        $this->laminasRouter
            ->match(Argument::type(LaminasRequest::class))
            ->willReturn($routeMatch->reveal());

        $request = $this->createRequestProphecy();

        $router = $this->getRouter();
        $result = $router->match($request->reveal());
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isSuccess());
        $this->assertEquals('/foo', $result->getMatchedRouteName());
        $this->assertEquals('bar', $result->getMatchedMiddleware());
    }

    /**
     * @group match
     */
    public function testNonSuccessfulMatchNotDueToHttpMethodsIsPossible()
    {
        $this->laminasRouter
            ->match(Argument::type(LaminasRequest::class))
            ->willReturn(null);

        $request = $this->createRequestProphecy();

        $router = $this->getRouter();
        $result = $router->match($request->reveal());
        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertFalse($result->isMethodFailure());
    }

    /**
     * @group match
     */
    public function testMatchFailureDueToHttpMethodReturnsRouteResultWithAllowedMethods()
    {
        $router = new LaminasRouter();
        $router->addRoute(new Route('/foo', 'bar', ['POST', 'DELETE']));
        $request = new ServerRequest([ 'REQUEST_METHOD' => 'GET' ], [], '/foo', 'GET');
        $result = $router->match($request);

        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertTrue($result->isMethodFailure());
        $this->assertEquals(['POST', 'DELETE'], $result->getAllowedMethods());
    }

    /**
     * @group match
     */
    public function testMatchFailureDueToMethodNotAllowedWithParamsInTheRoute()
    {
        $router = new LaminasRouter();
        $router->addRoute(new Route('/foo[/:id]', 'foo', ['POST', 'DELETE']));
        $request = new ServerRequest([ 'REQUEST_METHOD' => 'GET' ], [], '/foo/1', 'GET');
        $result = $router->match($request);

        $this->assertInstanceOf(RouteResult::class, $result);
        $this->assertTrue($result->isFailure());
        $this->assertTrue($result->isMethodFailure());
        $this->assertEquals(['POST', 'DELETE'], $result->getAllowedMethods());
    }

    /**
     * @group 53
     */
    public function testCanGenerateUriFromRoutes()
    {
        $router = new LaminasRouter();
        $route1 = new Route('/foo', 'foo', ['POST'], 'foo-create');
        $route2 = new Route('/foo', 'foo', ['GET'], 'foo-list');
        $route3 = new Route('/foo/:id', 'foo', ['GET'], 'foo');
        $route4 = new Route('/bar/:baz', 'bar', Route::HTTP_METHOD_ANY, 'bar');

        $router->addRoute($route1);
        $router->addRoute($route2);
        $router->addRoute($route3);
        $router->addRoute($route4);

        $this->assertEquals('/foo', $router->generateUri('foo-create'));
        $this->assertEquals('/foo', $router->generateUri('foo-list'));
        $this->assertEquals('/foo/bar', $router->generateUri('foo', ['id' => 'bar']));
        $this->assertEquals('/bar/BAZ', $router->generateUri('bar', ['baz' => 'BAZ']));
    }
}
