<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\Application;
use Mezzio\ApplicationPipeline;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\PathBasedRoutingMiddleware;
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
 * - Mezzio\Middleware\RouteMiddleware.
 * - Laminas\HttpHandler\RequestHandlerRunner.
 */
class ApplicationFactory
{
    public function __invoke(ContainerInterface $container) : Application
    {
        return new Application(
            $container->get(MiddlewareFactory::class),
            $container->get(ApplicationPipeline::class),
            $container->get(PathBasedRoutingMiddleware::class),
            $container->get(RequestHandlerRunner::class)
        );
    }
}
