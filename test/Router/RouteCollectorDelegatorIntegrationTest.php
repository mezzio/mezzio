<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Laminas\Router\ConfigProvider as LaminasRouterConfigProvider;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\InvokableFactory;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\ConfigProvider as MezzioConfigProvider;
use Mezzio\Router\ConfigProvider as RouterConfigProvider;
use Mezzio\Router\LaminasRouter\ConfigProvider as MezzioLaminasRouterConfigProvider;
use Mezzio\Router\Route;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteCollectorInterface;
use PHPUnit\Framework\TestCase;

use function array_filter;
use function array_merge_recursive;
use function assert;
use function count;
use function is_array;
use function reset;
use function sprintf;

/** @psalm-import-type ServiceManagerConfiguration from ServiceManager */
final class RouteCollectorDelegatorIntegrationTest extends TestCase
{
    protected function setUp(): void
    {
    }

    private function serviceManagerWithConfig(array $config): ServiceManager
    {
        $config = array_merge_recursive(
            (new MezzioConfigProvider())(),
            (new RouterConfigProvider())(),
            (new LaminasRouterConfigProvider())(),
            (new MezzioLaminasRouterConfigProvider())(),
            $config,
        );

        $dependencies = $config['dependencies'] ?? [];
        assert(is_array($dependencies));
        /** @psalm-suppress MixedAssignment */
        $dependencies['services'] = $dependencies['services'] ?? [];
        assert(is_array($dependencies['services']));
        $dependencies['services']['config'] = $config;
        /** @psalm-var ServiceManagerConfiguration $dependencies */

        return new ServiceManager($dependencies);
    }

    public function testThatAnExceptionIsThrownWhenAListedRouteProviderIsNotAvailableInTheContainer(): void
    {
        $config = [
            'router' => [
                'route-providers' => [
                    ExampleRouteProvider::class,
                ],
            ],
        ];

        $container = $this->serviceManagerWithConfig($config);

        $this->expectException(ServiceNotFoundException::class);
        $this->expectExceptionMessage(ExampleRouteProvider::class);

        $container->get(RouteCollectorInterface::class);
    }

    public function testThatTheRouteWillBeInjectedIntoTheRouteCollectorWhenAFactoryIsDefinedForTheProvider(): void
    {
        $config = [
            'dependencies' => [
                'factories' => [
                    ExampleRouteProvider::class => InvokableFactory::class,
                ],
            ],
            'router'       => [
                'route-providers' => [
                    ExampleRouteProvider::class,
                ],
            ],
        ];

        $container = $this->serviceManagerWithConfig($config);

        $collector = $container->get(RouteCollectorInterface::class);
        assert($collector instanceof RouteCollector);

        $this->assertContainsRouteWithNameAndPath('example-route', '/example-route', $collector->getRoutes());
    }

    private function assertContainsRouteWithNameAndPath(string $name, string $path, array $routes): void
    {
        self::assertGreaterThanOrEqual(1, count($routes), 'Expected a non-empty list of routes');

        $routes = array_filter($routes, static fn (Route $route): bool => $route->getName() === $name);

        self::assertCount(1, $routes, sprintf(
            'Expected the list of routes to contain a route with the name "%s" but none was found',
            $name,
        ));

        $route = reset($routes);
        self::assertInstanceOf(Route::class, $route);

        self::assertSame($path, $route->getPath());
    }
}
