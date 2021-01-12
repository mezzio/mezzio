<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use ArrayObject;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Application;
use Mezzio\Container\ApplicationConfigInjectionDelegator;
use Mezzio\Container\Exception\InvalidServiceException;
use Mezzio\Exception\InvalidArgumentException;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\MethodNotAllowedMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouterInterface;
use MezzioTest\InMemoryContainer;
use MezzioTest\TestAsset\InvokableMiddleware;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionProperty;
use SplQueue;

use function array_merge;
use function array_reduce;
use function array_shift;

class ApplicationConfigInjectionDelegatorTest extends TestCase
{
    /** @var InMemoryContainer */
    private $container;

    /** @var DispatchMiddleware|ObjectProphecy */
    private $dispatchMiddleware;

    /** @var MethodNotAllowedMiddleware|ObjectProphecy */
    private $methodNotAllowedMiddleware;

    /** @var RouteCollector */
    private $routeCollector;

    /** @var RouteMiddleware */
    private $routeMiddleware;

    /** @var RouterInterface|ObjectProphecy */
    private $router;

    public function setUp() : void
    {
        $this->container = new InMemoryContainer();
        $this->router = $this->prophesize(RouterInterface::class);
        $this->routeCollector = new RouteCollector($this->router->reveal());
        $this->routeMiddleware = new RouteMiddleware($this->router->reveal());
        $this->dispatchMiddleware = $this->prophesize(DispatchMiddleware::class)->reveal();
        $this->methodNotAllowedMiddleware = $this->prophesize(MethodNotAllowedMiddleware::class)->reveal();
    }

    public function createApplication() : Application
    {
        $container = new MiddlewareContainer($this->container);
        $factory = new MiddlewareFactory($container);
        $pipeline = new MiddlewarePipe();
        $runner = $this->prophesize(RequestHandlerRunner::class)->reveal();
        return new Application(
            $factory,
            $pipeline,
            $this->routeCollector,
            $runner
        );
    }

    public function getQueueFromApplicationPipeline(Application $app) : SplQueue
    {
        $r = new ReflectionProperty($app, 'pipeline');
        $r->setAccessible(true);
        $pipeline = $r->getValue($app);

        $r = new ReflectionProperty($pipeline, 'pipeline');
        $r->setAccessible(true);
        return $r->getValue($pipeline);
    }

    public static function assertRoute($spec, array $routes) : void
    {
        Assert::assertThat(
            array_reduce($routes, function ($found, $route) use ($spec) {
                if ($found) {
                    return $found;
                }

                if ($route->getPath() !== $spec['path']) {
                    return false;
                }

                if (! $route->getMiddleware() instanceof MiddlewareInterface) {
                    return false;
                }

                if (isset($spec['allowed_methods'])
                    && $route->getAllowedMethods() !== $spec['allowed_methods']
                ) {
                    return false;
                }

                if (! isset($spec['allowed_methods'])
                    && $route->getAllowedMethods() !== Route::HTTP_METHOD_ANY
                ) {
                    return false;
                }

                return true;
            }, false),
            Assert::isTrue(),
            'Route created does not match any specifications'
        );
    }

    public static function assertPipelineContainsInstanceOf($class, $pipeline, $message = null) : void
    {
        $message = $message ?: 'Did not find expected middleware class type in pipeline';
        $found   = false;

        foreach ($pipeline as $middleware) {
            if ($middleware instanceof $class) {
                $found = true;
                break;
            }
        }

        Assert::assertThat($found, Assert::isTrue(), $message);
    }

    public function callableMiddlewares() : array
    {
        return [
            ['HelloWorld'],
            [
                function () {
                },
            ],
            [[InvokableMiddleware::class, 'staticallyCallableMiddleware']],
        ];
    }

    public function testInvocationAsDelegatorFactoryRaisesExceptionIfCallbackIsNotAnApplication() : void
    {
        $callback = function () {
            return $this;
        };
        $factory = new ApplicationConfigInjectionDelegator();
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage('cannot operate');
        $factory($this->container, Application::class, $callback);
    }

    /**
     * @dataProvider callableMiddlewares
     *
     * @param callable|array|string $middleware
     */
    public function testInjectRoutesFromConfigSetsUpRoutesFromConfig($middleware) : void
    {
        $this->container->set('HelloWorld', true);
        $this->container->set('Ping', true);

        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => $middleware,
                    'allowed_methods' => ['GET'],
                ],
                [
                    'path' => '/ping',
                    'middleware' => 'Ping',
                    'allowed_methods' => ['GET'],
                ],
            ],
        ];

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        $routes = $app->getRoutes();

        foreach ($config['routes'] as $route) {
            $this->assertRoute($route, $routes);
        }
    }

    public function testNoRoutesAreAddedIfSpecDoesNotProvidePathOrMiddleware() : void
    {
        $config = [
            'routes' => [
                [
                    'allowed_methods' => ['GET'],
                ],
                [
                    'allowed_methods' => ['POST'],
                ],
            ],
        ];

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        $routes = $app->getRoutes();
        $this->assertCount(0, $routes);
    }

    public function testInjectPipelineFromConfigHonorsPriorityOrderWhenAttachingMiddleware() : void
    {
        $middleware = new TestAsset\InteropMiddleware();

        $pipeline1 = [['middleware' => clone $middleware, 'priority' => 1]];
        $pipeline2 = [['middleware' => clone $middleware, 'priority' => 100]];
        $pipeline3 = [['middleware' => clone $middleware, 'priority' => -100]];

        $pipeline = array_merge($pipeline3, $pipeline1, $pipeline2);
        $config = ['middleware_pipeline' => $pipeline];

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, $config);

        $pipeline = $this->getQueueFromApplicationPipeline($app);

        $this->assertSame($pipeline2[0]['middleware'], $pipeline->dequeue());
        $this->assertSame($pipeline1[0]['middleware'], $pipeline->dequeue());
        $this->assertSame($pipeline3[0]['middleware'], $pipeline->dequeue());
    }

    public function testMiddlewareWithoutPriorityIsGivenDefaultPriorityAndRegisteredInOrderReceived() : void
    {
        $middleware = new TestAsset\InteropMiddleware();

        $pipeline1 = [['middleware' => clone $middleware]];
        $pipeline2 = [['middleware' => clone $middleware]];
        $pipeline3 = [['middleware' => clone $middleware]];

        $pipeline = array_merge($pipeline3, $pipeline1, $pipeline2);
        $config = ['middleware_pipeline' => $pipeline];

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, $config);

        $pipeline = $this->getQueueFromApplicationPipeline($app);

        $this->assertSame($pipeline3[0]['middleware'], $pipeline->dequeue());
        $this->assertSame($pipeline1[0]['middleware'], $pipeline->dequeue());
        $this->assertSame($pipeline2[0]['middleware'], $pipeline->dequeue());
    }

    public function testInjectPipelineFromConfigWithEmptyConfigDoesNothing() : void
    {
        $app = $this->createApplication();
        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, []);
        $pipeline = $this->getQueueFromApplicationPipeline($app);
        $this->assertEquals(0, $pipeline->count());
    }

    public function testInjectRoutesFromConfigWithEmptyConfigDoesNothing() : void
    {
        $app = $this->createApplication();
        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, []);
        $this->assertEquals([], $app->getRoutes());
        $pipeline = $this->getQueueFromApplicationPipeline($app);
        $this->assertEquals(0, $pipeline->count());
    }

    public function testInjectRoutesFromConfigRaisesExceptionIfAllowedMethodsIsInvalid() : void
    {
        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => new TestAsset\InteropMiddleware(),
                    'allowed_methods' => 'not-valid',
                ],
            ],
        ];

        $app = $this->createApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Allowed HTTP methods');
        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);
    }

    public function testInjectRoutesFromConfigRaisesExceptionIfOptionsIsNotAnArray() : void
    {
        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => new TestAsset\InteropMiddleware(),
                    'allowed_methods' => ['GET'],
                    'options' => 'invalid',
                ],
            ],
        ];

        $app = $this->createApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route options must be an array');
        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);
    }

    public function testInjectRoutesFromConfigCanProvideRouteOptions() : void
    {
        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'middleware' => new TestAsset\InteropMiddleware(),
                    'allowed_methods' => ['GET'],
                    'options' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        $routes = $app->getRoutes();

        $route = array_shift($routes);
        $this->assertEquals($config['routes'][0]['options'], $route->getOptions());
    }

    public function testInjectRoutesFromConfigWillSkipSpecsThatOmitPath() : void
    {
        $config = [
            'routes' => [
                [
                    'middleware' => new TestAsset\InteropMiddleware(),
                    'allowed_methods' => ['GET'],
                    'options' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];


        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, $config);
        $this->assertEquals([], $app->getRoutes());
    }

    public function testInjectRoutesFromConfigWillSkipSpecsThatOmitMiddleware() : void
    {
        $config = [
            'routes' => [
                [
                    'path' => '/',
                    'allowed_methods' => ['GET'],
                    'options' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
        ];

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, $config);
        $this->assertEquals([], $app->getRoutes());
    }

    public function testInjectRoutesFromConfigSetRouteNameViaArrayKey() : void
    {
        $config = [
            'routes' => [
                'home' => [
                    'path' => '/',
                    'middleware' => new TestAsset\InteropMiddleware(),
                ],
            ],
        ];

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        $routes = $app->getRoutes();

        $route = array_shift($routes);
        $this->assertEquals('home', $route->getName());
    }

    public function testInjectRoutesFromConfigRouteSpecNameOverrideArrayKeyName() : void
    {
        $config = [
            'routes' => [
                'home' => [
                    'name' => 'homepage',
                    'path' => '/',
                    'middleware' => new TestAsset\InteropMiddleware(),
                ],
            ],
        ];

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        $routes = $app->getRoutes();

        $route = array_shift($routes);
        $this->assertEquals('homepage', $route->getName());
    }

    public function testInjectPipelineFromConfigRaisesExceptionForSpecsOmittingMiddlewareKey() : void
    {
        $config = [
            'middleware_pipeline' => [
                [
                    'this' => 'will not work',
                ],
            ],
        ];
        $app = $this->createApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid pipeline specification received');
        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, $config);
    }

    public function testConfigCanBeArrayObject() : void
    {
        $config = new ArrayObject([
            'routes' => [
                'home' => [
                    'name' => 'homepage',
                    'path' => '/',
                    'middleware' => new TestAsset\InteropMiddleware(),
                ],
            ],
        ]);

        $this->container->set('config', $config);

        $delegator = new ApplicationConfigInjectionDelegator();
        $application = $delegator($this->container, '', function () {
            return $this->createApplication();
        });

        $this->assertCount(1, $application->getRoutes());
    }
}
