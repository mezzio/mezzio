<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Template\LaminasView;

use Laminas\View\Helper\AbstractHelper;
use Mezzio\Exception;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;

class UrlHelper extends AbstractHelper
{
    /**
     * @var RouteResult
     */
    private $result;

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * @param string $route
     * @param array $params
     * @return string
     * @throws Exception\RenderingException if no route provided, and no result
     *     match present.
     * @throws Exception\RenderingException if no route provided, and result
     *     match is a routing failure.
     * @throws Exception\RuntimeException if router cannot generate URI for
     *     given route.
     */
    public function __invoke($route = null, $params = [])
    {
        if ($route === null && $this->result === null) {
            throw new Exception\RenderingException(
                'Attempting to use matched result when none was injected; aborting'
            );
        }

        if ($route === null) {
            return $this->generateUriFromResult($params);
        }

        return $this->router->generateUri($route, $params);
    }

    /**
     * @param RouteResult $result
     */
    public function setRouteResult(RouteResult $result)
    {
        $this->result = $result;
    }

    /**
     * @param array $params
     * @return string
     * @throws Exception\RenderingException if current result is a routing
     *     failure.
     */
    private function generateUriFromResult(array $params)
    {
        if ($this->result->isFailure()) {
            throw new Exception\RenderingException(
                'Attempting to use matched result when routing failed; aborting'
            );
        }

        $name   = $this->result->getMatchedRouteName();
        $params = array_merge($this->result->getMatchedParams(), $params);
        return $this->router->generateUri($name, $params);
    }
}
