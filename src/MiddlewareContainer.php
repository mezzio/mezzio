<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio;

use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareContainer implements ContainerInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * Returns true if the service is in the container, or resolves to an
     * autoloadable class name.
     * {@inheritDocs}
     */
    public function has($service) : bool
    {
        if ($this->container->has($service)) {
            return true;
        }

        return class_exists($service);
    }

    /**
     * Returns middleware pulled from container, or directly instantiated if
     * not managed by the container.
     * {@inheritDocs}
     * @throws Exception\MissingDependencyException if the service does not
     *     exist, or is not a valid class name.
     */
    public function get($service) : MiddlewareInterface
    {
        if (! $this->has($service)) {
            throw Exception\MissingDependencyException::forMiddlewareService($service);
        }

        $middleware = $this->container->has($service)
            ? $this->container->get($service)
            : new $service();

        $middleware = $middleware instanceof RequestHandlerInterface
            ? new RequestHandlerMiddleware($middleware)
            : $middleware;

        if (! $middleware instanceof MiddlewareInterface) {
            throw Exception\InvalidMiddlewareException::forMiddlewareService($service, $middleware);
        }

        return $middleware;
    }
}
