<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Middleware;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Mezzio\MarshalMiddlewareTrait;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Default dispatch middleware.
 *
 * Checks for a composed route result in the request. If none is provided,
 * delegates to the next middleware.
 *
 * Otherwise, it pulls the middleware from the route result. If the middleware
 * is not http-interop middleware, it uses the composed router, response
 * prototype, and container to prepare it, via the
 * `MarshalMiddlewareTrait::prepareMiddleware()` method. In each case, it then
 * processes the middleware.
 *
 * @internal
 */
class DispatchMiddleware implements MiddlewareInterface
{
    use MarshalMiddlewareTrait;

    /**
     * @var ContainerInterface|null
     */
    private $container;

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(
        RouterInterface $router,
        ResponseInterface $responsePrototype,
        ContainerInterface $container = null
    ) {
        $this->router = $router;
        $this->responsePrototype = $responsePrototype;
        $this->container = $container;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $routeResult = $request->getAttribute(RouteResult::class, false);
        if (! $routeResult) {
            return $handler->handle($request);
        }

        $middleware = $routeResult->getMatchedMiddleware();

        if (! $middleware instanceof MiddlewareInterface) {
            $middleware = $this->prepareMiddleware(
                $middleware,
                $this->router,
                $this->responsePrototype,
                $this->container
            );
        }

        return $middleware->process($request, $handler);
    }
}
