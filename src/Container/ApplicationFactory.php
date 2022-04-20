<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Mezzio\Application;
use Mezzio\ApplicationPipeline;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouteCollectorInterface;
use Psr\Container\ContainerInterface;

/**
 * Create an Application instance.
 *
 * This class consumes three other services, and one pseudo-service (service
 * that looks like a class name, but resolves to a different resource):
 *
 * - Mezzio\MiddlewareFactory.
 * - Mezzio\ApplicationPipeline, which should resolve to a
 *   Laminas\Stratigility\MiddlewarePipeInterface instance.
 * - Mezzio\Router\RouteCollector.
 * - Laminas\HttpHandler\RequestHandlerRunner.
 */
class ApplicationFactory
{
    public function __invoke(ContainerInterface $container): Application
    {
        return new Application(
            $container->get(MiddlewareFactory::class),
            $container->get(ApplicationPipeline::class),
            $container->has(RouteCollectorInterface::class) ?
                $container->get(RouteCollectorInterface::class) :
                $container->get(RouteCollector::class),
            $container->get(RequestHandlerRunnerInterface::class)
        );
    }
}
