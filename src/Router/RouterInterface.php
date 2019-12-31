<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Router;

use Mezzio\Exception;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * Interface defining required router capabilities.
 */
interface RouterInterface
{
    /**
     * @param Route $route
     */
    public function addRoute(Route $route);

    /**
     * @param  Request $request
     * @return RouteResult
     */
    public function match(Request $request);

    /**
     * Generate a URI from the named route.
     *
     * Takes the named route and any substitutions, and attempts to generate a
     * URI from it.
     *
     * @see https://github.com/auraphp/Aura.Router#generating-a-route-path
     * @see https://docs.laminas.dev/laminas.mvc.routing.html
     * @param string $name
     * @param array $substitutions
     * @return string
     * @throws Exception\RuntimeException if unable to generate the given URI.
     */
    public function generateUri($name, array $substitutions = []);
}
