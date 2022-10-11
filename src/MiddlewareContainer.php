<?php

declare(strict_types=1);

namespace Mezzio;

use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function class_exists;

class MiddlewareContainer implements ContainerInterface
{
    public function __construct(private ContainerInterface $container)
    {
    }

    /**
     * Returns true if the service is in the container, or resolves to an
     * autoloadable class name.
     *
     * @param string $service
     */
    public function has($service): bool
    {
        if ($this->container->has($service)) {
            return true;
        }

        return class_exists($service);
    }

    /**
     * Returns middleware pulled from container, or directly instantiated if
     * not managed by the container.
     *
     * @param string $service
     * @throws Exception\MissingDependencyException If the service does not
     *     exist, or is not a valid class name.
     * @throws Exception\InvalidMiddlewareException If the service is not
     *     an instance of MiddlewareInterface.
     */
    public function get($service): MiddlewareInterface
    {
        if (! $this->has($service)) {
            throw Exception\MissingDependencyException::forMiddlewareService($service);
        }

        $middleware = $this->container->has($service)
            ? $this->container->get($service)
            : new $service();

        if (
            $middleware instanceof RequestHandlerInterface
            && ! $middleware instanceof MiddlewareInterface
        ) {
            $middleware = new RequestHandlerMiddleware($middleware);
        }

        if (! $middleware instanceof MiddlewareInterface) {
            throw Exception\InvalidMiddlewareException::forMiddlewareService($service, $middleware);
        }

        return $middleware;
    }
}
