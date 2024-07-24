<?php

declare(strict_types=1);

namespace Mezzio\Router;

use Laminas\ServiceManager\Factory\DelegatorFactoryInterface;
use Mezzio\MiddlewareFactoryInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function assert;
use function is_array;
use function is_string;

final class RouteCollectorDelegator implements DelegatorFactoryInterface
{
    /**
     * RouteCollector Delegator
     *
     * Delegates around the RouteCollectorInterface and triggers all registered route providers prior to returning the
     * RouteCollector instance.
     *
     * @inheritDoc
     */
    public function __invoke(
        ContainerInterface $container,
        $name,
        callable $callback,
        ?array $options = null
    ): RouteCollectorInterface {
        $collector = $callback();
        Assert::isInstanceOf($collector, RouteCollectorInterface::class);

        $config = $container->get('config');
        Assert::isArray($config);

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
