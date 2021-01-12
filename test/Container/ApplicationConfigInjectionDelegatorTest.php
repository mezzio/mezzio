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
use MezzioTest\ContainerTrait;
use MezzioTest\TestAsset\InvokableMiddleware;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use ReflectionProperty;

use function array_merge;
use function array_reduce;
use function array_shift;

class ApplicationConfigInjectionDelegatorTest extends TestCase
{
    use ContainerTrait, ProphecyTrait;

    /** @var ContainerInterface|ObjectProphecy */
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

    public function setUp(): void
    {
        $this->container = $this->mockContainerInterface();
        $this->router = $this->prophesize(RouterInterface::class);
        $this->routeCollector = new RouteCollector($this->router->reveal());
        $this->routeMiddleware = new RouteMiddleware($this->router->reveal());
        $this->dispatchMiddleware = $this->prophesize(DispatchMiddleware::class)->reveal();
        $this->methodNotAllowedMiddleware = $this->prophesize(MethodNotAllowedMiddleware::class)->reveal();
    }

    public function createApplication()
    {
        $container = new MiddlewareContainer($this->container->reveal());
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

    public function getQueueFromApplicationPipeline(Application $app)
    {
        $r = new ReflectionProperty($app, 'pipeline');
        $r->setAccessible(true);
        $pipeline = $r->getValue($app);

        $r = new ReflectionProperty($pipeline, 'pipeline');
        $r->setAccessible(true);
        return $r->getValue($pipeline);
    }

    public static function assertRoute($spec, array $routes)
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

    public static function assertPipelineContainsInstanceOf($class, $pipeline, $message = null)
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

    public function callableMiddlewares()
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

    public function testInvocationAsDelegatorFactoryRaisesExceptionIfCallbackIsNotAnApplication()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $callback = function () {
            return $this;
        };
        $factory = new ApplicationConfigInjectionDelegator();
        $this->expectException(InvalidServiceException::class);
        $this->expectExceptionMessage('cannot operate');
        $factory($container, Application::class, $callback);
    }

    /**
     * @dataProvider callableMiddlewares
     *
     * @param callable|array|string $middleware
     */
    public function testInjectRoutesFromConfigSetsUpRoutesFromConfig($middleware)
    {
        $this->container->has('HelloWorld')->willReturn(true);
        $this->container->has('Ping')->willReturn(true);

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

    public function testNoRoutesAreAddedIfSpecDoesNotProvidePathOrMiddleware()
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

    public function testInjectPipelineFromConfigHonorsPriorityOrderWhenAttachingMiddleware()
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

    public function testMiddlewareWithoutPriorityIsGivenDefaultPriorityAndRegisteredInOrderReceived()
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

    public function testInjectPipelineFromConfigWithEmptyConfigDoesNothing()
    {
        $app = $this->createApplication();
        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, []);
        $pipeline = $this->getQueueFromApplicationPipeline($app);
        $this->assertEquals(0, $pipeline->count());
    }

    public function testInjectRoutesFromConfigWithEmptyConfigDoesNothing()
    {
        $app = $this->createApplication();
        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, []);
        $this->assertEquals([], $app->getRoutes());
        $pipeline = $this->getQueueFromApplicationPipeline($app);
        $this->assertEquals(0, $pipeline->count());
    }

    public function testInjectRoutesFromConfigRaisesExceptionIfAllowedMethodsIsInvalid()
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
        $this->container->has('config')->willReturn(false);
        $app = $this->createApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Allowed HTTP methods');
        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);
    }

    public function testInjectRoutesFromConfigRaisesExceptionIfOptionsIsNotAnArray()
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
        $this->container->has('config')->willReturn(false);
        $app = $this->createApplication();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Route options must be an array');
        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);
    }

    public function testInjectRoutesFromConfigCanProvideRouteOptions()
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
        $this->container->has('config')->willReturn(false);
        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        $routes = $app->getRoutes();

        $route = array_shift($routes);
        $this->assertEquals($config['routes'][0]['options'], $route->getOptions());
    }

    public function testInjectRoutesFromConfigWillSkipSpecsThatOmitPath()
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
        $this->container->has('config')->willReturn(false);

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, $config);
        $this->assertEquals([], $app->getRoutes());
    }

    public function testInjectRoutesFromConfigWillSkipSpecsThatOmitMiddleware()
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
        $this->container->has('config')->willReturn(false);

        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectPipelineFromConfig($app, $config);
        $this->assertEquals([], $app->getRoutes());
    }

    public function testInjectRoutesFromConfigSetRouteNameViaArrayKey()
    {
        $config = [
            'routes' => [
                'home' => [
                    'path' => '/',
                    'middleware' => new TestAsset\InteropMiddleware(),
                ],
            ],
        ];
        $this->container->has('config')->willReturn(false);
        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        $routes = $app->getRoutes();

        $route = array_shift($routes);
        $this->assertEquals('home', $route->getName());
    }

    public function testInjectRoutesFromConfigRouteSpecNameOverrideArrayKeyName()
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
        $this->container->has('config')->willReturn(false);
        $app = $this->createApplication();

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig($app, $config);

        $routes = $app->getRoutes();

        $route = array_shift($routes);
        $this->assertEquals('homepage', $route->getName());
    }

    public function testInjectPipelineFromConfigRaisesExceptionForSpecsOmittingMiddlewareKey()
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

    public function testConfigCanBeArrayObject()
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

        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $delegator = new ApplicationConfigInjectionDelegator();
        $application = $delegator($this->container->reveal(), '', function () {
            return $this->createApplication();
        });

        $this->assertCount(1, $application->getRoutes());
    }
}
