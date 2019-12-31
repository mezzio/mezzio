<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest;

use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;

trait RouteResultTrait
{
    private function getRouteResult($name, $middleware, array $params)
    {
        if (method_exists(RouteResult::class, 'fromRouteMatch')) {
            return RouteResult::fromRouteMatch($name, $middleware, $params);
        }

        $route = $this->prophesize(Route::class);
        $route->getMiddleware()->willReturn($middleware);
        $route->getPath()->willReturn($name);
        $route->getName()->willReturn(null);

        return RouteResult::fromRoute($route->reveal(), $params);
    }
}
