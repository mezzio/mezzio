<?php

declare(strict_types=1);

namespace MezzioTest\Router;

use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Router\RouteCollectorInterface;
use Mezzio\Router\RouteProviderInterface;
use MezzioTest\TestAsset\NoOpMiddleware;

final class ExampleRouteProvider implements RouteProviderInterface
{
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory,
    ): void {
        $routeCollector->get(
            '/example-route',
            $middlewareFactory->prepare([NoOpMiddleware::class]),
            'example-route',
        );
    }
}
