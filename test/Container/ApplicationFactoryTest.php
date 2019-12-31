<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Container;

use Mezzio\Application;
use Mezzio\Container\ApplicationFactory;
use Mezzio\Router\Route;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionFunction;
use ReflectionProperty;

class ApplicationFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize('Interop\Container\ContainerInterface');
        $this->factory   = new ApplicationFactory();
    }

    public function assertRoute($spec, array $routes)
    {
        $this->assertTrue(array_reduce($routes, function ($found, $route) use ($spec) {
            if ($found) {
                return $found;
            }

            if ($route->getPath() !== $spec['path']) {
                return false;
            }

            if ($route->getMiddleware() !== $spec['middleware']) {
                return false;
            }

            if (isset($spec['allowed_methods'])) {
                if ($route->getAllowedMethods() !== $spec['allowed_methods']) {
                    return false;
                }
            }

            if (! isset($spec['allowed_methods'])) {
                if ($route->getAllowedMethods() !== Route::HTTP_METHOD_ANY) {
                    return false;
                }
            }

            return true;
        }, false));
    }

    public function getRouterFromApplication(Application $app)
    {
        $r = new ReflectionProperty($app, 'router');
        $r->setAccessible(true);
        return $r->getValue($app);
    }

    public function testFactoryWillPullAllReplaceableDependenciesFromContainerWhenPresent()
    {
        $router       = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter      = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(false);

        $app = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf('Mezzio\Application', $app);
        $test = $this->getRouterFromApplication($app);
        $this->assertSame($router->reveal(), $test);
        $this->assertSame($this->container->reveal(), $app->getContainer());
        $this->assertSame($emitter->reveal(), $app->getEmitter());
        $this->assertSame($finalHandler, $app->getFinalHandler());
    }

    public function testFactorySetsUpRoutesFromConfig()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => 'HelloWorld',
                    'allowed_methods' => [ 'GET' ],
                ],
                [
                    'path' => '/ping',
                    'middleware' => 'Ping',
                    'allowed_methods' => [ 'GET' ],
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'routes');
        $r->setAccessible(true);
        $routes = $r->getValue($app);

        foreach ($config['routes'] as $route) {
            $this->assertRoute($route, $routes);
        }
    }

    public function testWillUseSaneDefaultsForOptionalServices()
    {
        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(false);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->shouldNotBeCalled();

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(false);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->shouldNotBeCalled();

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(false);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->shouldNotBeCalled();

        $this->container
            ->has('config')
            ->willReturn(false);

        $app = $this->factory->__invoke($this->container->reveal());
        $this->assertInstanceOf('Mezzio\Application', $app);
        $router = $this->getRouterFromApplication($app);
        $this->assertInstanceOf('Mezzio\Router\AuraRouter', $router);
        $this->assertSame($this->container->reveal(), $app->getContainer());
        $this->assertInstanceOf('Mezzio\Emitter\EmitterStack', $app->getEmitter());
        $this->assertCount(1, $app->getEmitter());
        $this->assertInstanceOf('Laminas\Diactoros\Response\SapiEmitter', $app->getEmitter()->pop());
        $this->assertNull($app->getFinalHandler());
    }

    /**
     * @group piping
     */
    public function testCanPipeMiddlewareProvidedDuringConfigurationPriorToSettingRoutes()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $middleware = function ($req, $res, $next = null) {
        };

        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => 'HelloWorld',
                    'allowed_methods' => [ 'GET' ],
                ],
            ],
            'middleware_pipeline' => [
                'pre_routing' => [
                    [ 'middleware' => $middleware ],
                    [ 'path' => '/foo', 'middleware' => $middleware ],
                ],
                'post_routing' => [ ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'pipeline');
        $r->setAccessible(true);
        $pipeline = $r->getValue($app);

        $this->assertCount(3, $pipeline);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertSame($middleware, $route->handler);
        $this->assertEquals('/', $route->path);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertSame($middleware, $route->handler);
        $this->assertEquals('/foo', $route->path);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertSame([$app, 'routeMiddleware'], $route->handler);
        $this->assertEquals('/', $route->path);
    }

    /**
     * @group piping
     */
    public function testCanPipeMiddlewareProvidedDuringConfigurationAfterSettingRoutes()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $middleware = function ($req, $res, $next = null) {
            return true;
        };

        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => 'HelloWorld',
                    'allowed_methods' => [ 'GET' ],
                ],
            ],
            'middleware_pipeline' => [
                'pre_routing' => [ ],
                'post_routing' => [
                    [ 'middleware' => $middleware ],
                    [ 'path' => '/foo', 'middleware' => $middleware ],
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'pipeline');
        $r->setAccessible(true);
        $pipeline = $r->getValue($app);

        $this->assertCount(3, $pipeline);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertSame([$app, 'routeMiddleware'], $route->handler);
        $this->assertEquals('/', $route->path);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertInstanceOf('Closure', $route->handler);
        $this->assertTrue(call_user_func($route->handler, 'req', 'res'));
        $this->assertEquals('/', $route->path);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertInstanceOf('Closure', $route->handler);
        $this->assertTrue(call_user_func($route->handler, 'req', 'res'));
        $this->assertEquals('/foo', $route->path);
    }

    /**
     * @group piping
     */
    public function testPipedMiddlewareAsServiceNamesAreReturnedAsClosuresThatPullFromContainer()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $middleware = function ($req, $res, $next = null) {
            return true;
        };

        $config = [
            'middleware_pipeline' => [
                'post_routing' => [
                    [ 'middleware' => 'Middleware' ],
                    [ 'path' => '/foo', 'middleware' => 'Middleware' ],
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $this->container
            ->has('Middleware')
            ->willReturn(true);

        $this->container
            ->get('Middleware')
            ->willReturn($middleware);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'pipeline');
        $r->setAccessible(true);
        $pipeline = $r->getValue($app);

        $this->assertCount(3, $pipeline);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertSame([$app, 'routeMiddleware'], $route->handler);
        $this->assertEquals('/', $route->path);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertInstanceOf('Closure', $route->handler);
        $this->assertTrue(call_user_func($route->handler, 'req', 'res'));
        $this->assertEquals('/', $route->path);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertInstanceOf('Closure', $route->handler);
        $this->assertTrue(call_user_func($route->handler, 'req', 'res'));
        $this->assertEquals('/foo', $route->path);
    }

    public function uncallableMiddleware()
    {
        return [
            'null'       => [null],
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['Middleware']],
            'object'     => [(object) ['call' => 'Middleware']],
        ];
    }

    /**
     * @group fail
     * @group piping
     * @dataProvider uncallableMiddleware
     */
    public function testRaisesExceptionForNonCallableNonServiceMiddleware($middleware)
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $config = [
            'middleware_pipeline' => [
                'post_routing' => [
                    [ 'middleware' => $middleware ],
                    [ 'path' => '/foo', 'middleware' => $middleware ],
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $this->container
            ->has('/')
            ->willReturn(false);

        $this->setExpectedException('InvalidArgumentException');
        $app = $this->factory->__invoke($this->container->reveal());
    }

    /**
     * @group piping
     */
    public function testRaisesExceptionForPipedMiddlewareServiceNamesNotFoundInContainer()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $config = [
            'middleware_pipeline' => [
                'post_routing' => [
                    [ 'middleware' => 'Middleware' ],
                    [ 'path' => '/foo', 'middleware' => 'Middleware' ],
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $this->container
            ->has('Middleware')
            ->willReturn(false);

        $this->setExpectedException('InvalidArgumentException');
        $app = $this->factory->__invoke($this->container->reveal());
    }

    /**
     * @group piping
     */
    public function testRaisesExceptionOnInvocationOfUninvokableServiceSpecifiedMiddlewarePulledFromContainer()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $middleware = (object) [];

        $config = [
            'middleware_pipeline' => [
                'post_routing' => [
                    [ 'middleware' => 'Middleware' ],
                    [ 'path' => '/foo', 'middleware' => 'Middleware' ],
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $this->container
            ->has('Middleware')
            ->willReturn(true);

        $this->container
            ->get('Middleware')
            ->willReturn($middleware);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'pipeline');
        $r->setAccessible(true);
        $pipeline = $r->getValue($app);

        $this->assertCount(3, $pipeline);

        $routing = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $routing);
        $this->assertSame([$app, 'routeMiddleware'], $routing->handler);
        $this->assertEquals('/', $routing->path);

        $first = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $first);
        $this->assertInstanceOf('Closure', $first->handler);
        $this->assertEquals('/', $first->path);

        $second = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $second);
        $this->assertInstanceOf('Closure', $second->handler);
        $this->assertEquals('/foo', $second->path);

        foreach (['first' => $first->handler, 'second' => $second->handler] as $index => $handler) {
            try {
                $handler('req', 'res');
                $this->fail(sprintf('%s handler succeed, but should have raised an exception', $index));
            } catch (\Exception $e) {
                $this->assertInstanceOf('Mezzio\Exception\InvalidMiddlewareException', $e);
                $this->assertContains('Lazy-loaded', $e->getMessage());
            }
        }
    }

    public function testCanSpecifyRouteViaConfigurationWithNoMethods()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => 'HelloWorld',
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'routes');
        $r->setAccessible(true);
        $routes = $r->getValue($app);

        foreach ($config['routes'] as $route) {
            $this->assertRoute($route, $routes);
        }
    }

    /**
     * @group piping
     */
    public function testCanMarkPipedMiddlewareServiceAsErrorMiddleware()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $middleware = function ($err, $req, $res, $next) {
            return true;
        };

        $config = [
            'middleware_pipeline' => [
                'post_routing' => [
                    [ 'middleware' => 'Middleware', 'error' => true ],
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $this->container
            ->has('Middleware')
            ->willReturn(true);

        $this->container
            ->get('Middleware')
            ->willReturn($middleware);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'pipeline');
        $r->setAccessible(true);
        $pipeline = $r->getValue($app);

        $this->assertCount(2, $pipeline);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertEquals('/', $route->path);
        $this->assertSame([$app, 'routeMiddleware'], $route->handler);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertEquals('/', $route->path);
        $this->assertInstanceOf('Closure', $route->handler);

        $r = new ReflectionFunction($route->handler);
        $this->assertEquals(4, $r->getNumberOfRequiredParameters());
        $this->assertTrue(call_user_func($route->handler, 'error', 'req', 'res', 'next'));
    }

    /**
     * @group 64
     */
    public function testWillPipeRoutingMiddlewareEvenIfNoRoutesAreRegistered()
    {
        $router  = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $middleware = function ($req, $res, $next = null) {
        };

        $config = [
            'middleware_pipeline' => [
                'pre_routing' => [
                    [ 'middleware' => $middleware ],
                    [ 'path' => '/foo', 'middleware' => $middleware ],
                ],
                'post_routing' => [ ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'pipeline');
        $r->setAccessible(true);
        $pipeline = $r->getValue($app);

        $this->assertCount(3, $pipeline);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertSame($middleware, $route->handler);
        $this->assertEquals('/', $route->path);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertSame($middleware, $route->handler);
        $this->assertEquals('/foo', $route->path);

        $route = $pipeline->dequeue();
        $this->assertInstanceOf('Laminas\Stratigility\Route', $route);
        $this->assertSame([$app, 'routeMiddleware'], $route->handler);
        $this->assertEquals('/', $route->path);
    }

    public function testCanSpecifyRouteNamesViaConfiguration()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => 'HelloWorld',
                    'name' => 'home',
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'routes');
        $r->setAccessible(true);
        $routes = $r->getValue($app);
        $route  = array_shift($routes);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals('home', $route->getName());
    }

    public function testCanSpecifyRouteOptionsViaConfiguration()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $emitter = $this->prophesize('Laminas\Diactoros\Response\EmitterInterface');
        $finalHandler = function ($req, $res, $err = null) {
        };

        $expected = [
            'values' => [
                'foo' => 'bar'
            ],
            'tokens' => [
                'bar' => 'foo'
            ]
        ];
        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => 'HelloWorld',
                    'name' => 'home',
                    'allowed_methods' => ['GET'],
                    'options' => $expected
                ],
            ],
        ];

        $this->container
            ->has('Mezzio\Router\RouterInterface')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\Router\RouterInterface')
            ->will(function () use ($router) {
                return $router->reveal();
            });

        $this->container
            ->has('Laminas\Diactoros\Response\EmitterInterface')
            ->willReturn(true);
        $this->container
            ->get('Laminas\Diactoros\Response\EmitterInterface')
            ->will(function () use ($emitter) {
                return $emitter->reveal();
            });

        $this->container
            ->has('Mezzio\FinalHandler')
            ->willReturn(true);
        $this->container
            ->get('Mezzio\FinalHandler')
            ->willReturn($finalHandler);

        $this->container
            ->has('config')
            ->willReturn(true);

        $this->container
            ->get('config')
            ->willReturn($config);

        $app = $this->factory->__invoke($this->container->reveal());

        $r = new ReflectionProperty($app, 'routes');
        $r->setAccessible(true);
        $routes = $r->getValue($app);
        $route  = array_shift($routes);

        $this->assertInstanceOf(Route::class, $route);
        $this->assertEquals($expected, $route->getOptions());
    }
}
