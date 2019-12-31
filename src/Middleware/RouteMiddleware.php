<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Middleware;

use Mezzio\Router\Middleware\RouteMiddleware as BaseRouteMiddleware;

/**
 * Default routing middleware.
 *
 * Uses the composed router to match against the incoming request.
 *
 * When routing failure occurs, if the failure is due to HTTP method, uses
 * the composed response prototype to generate a 405 response; otherwise,
 * it delegates to the next middleware.
 *
 * If routing succeeds, injects the route result into the request (under the
 * RouteResult class name), as well as any matched parameters, before
 * delegating to the next middleware.
 *
 * @deprecated since 2.2.0. This class is now a part of mezzio-router,
 *     and will be removed for the 3.0.0 release.
 * @internal
 */
class RouteMiddleware extends BaseRouteMiddleware
{
}
