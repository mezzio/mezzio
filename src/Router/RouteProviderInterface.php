<?php

declare(strict_types=1);

namespace Mezzio\Router;

use Mezzio\MiddlewareFactoryInterface;

interface RouteProviderInterface
{
    public function registerRoutes(
        RouteCollectorInterface $routeCollector,
        MiddlewareFactoryInterface $middlewareFactory,
    ): void;
}
