<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Response\RouterResponseInterface;
use Mezzio\Router\PathBasedRoutingMiddleware;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

class RouteMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : PathBasedRoutingMiddleware
    {
        return new PathBasedRoutingMiddleware(
            $container->get(RouterInterface::class),
            $container->get(RouterResponseInterface::class)
        );
    }
}
