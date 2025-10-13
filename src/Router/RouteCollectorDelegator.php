<?php

declare(strict_types=1);

namespace Mezzio\Router;

use Mezzio\MiddlewareFactoryInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function assert;
use function is_array;
use function is_string;

final class RouteCollectorDelegator
{
    /**
     * RouteCollector Delegator
     *
     * Delegates around the RouteCollectorInterface and triggers all registered route providers prior to returning the
     * RouteCollector instance.
     */
    public function __invoke(
        ContainerInterface $container,
        string $name,
        callable $callback,
    ): RouteCollectorInterface {
        $collector = $callback();
        Assert::isInstanceOf($collector, RouteCollectorInterface::class);

        $config = $container->get('config');
        Assert::isArrayAccessible($config);

        $routerConfig = $config['router'] ?? [];
        assert(is_array($routerConfig));
        $providers = $routerConfig['route-providers'] ?? [];
        assert(is_array($providers));

        $middlewareFactory = $container->get(MiddlewareFactoryInterface::class);
        foreach ($providers as $provider) {
            assert(is_string($provider));
            /** @psalm-suppress MixedAssignment */
            $provider = $container->get($provider);
            assert($provider instanceof RouteProviderInterface);

            $provider->registerRoutes($collector, $middlewareFactory);
        }

        return $collector;
    }
}
