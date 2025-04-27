<?php

declare(strict_types=1);

namespace Mezzio;

use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function class_exists;

/** @final */
class MiddlewareContainer implements ContainerInterface
{
    public function __construct(private readonly ContainerInterface $container)
    {
    }

    /**
     * Returns true if the service is in the container, or resolves to an
     * autoloadable class name.
     *
     * @param string $id
     */
    public function has($id): bool
    {
        if ($this->container->has($id)) {
            return true;
        }

        return class_exists($id);
    }

    /**
     * Returns middleware pulled from container, or directly instantiated if
     * not managed by the container.
     *
     * @param string $id
     * @throws Exception\MissingDependencyException If the service does not
     *     exist, or is not a valid class name.
     * @throws Exception\InvalidMiddlewareException If the service is not
     *     an instance of MiddlewareInterface.
     */
    public function get($id): MiddlewareInterface
    {
        if (! $this->has($id)) {
            throw Exception\MissingDependencyException::forMiddlewareService($id);
        }

        $middleware = $this->container->has($id)
            ? $this->container->get($id)
            : new $id();

        if (
            $middleware instanceof RequestHandlerInterface
            && ! $middleware instanceof MiddlewareInterface
        ) {
            $middleware = new RequestHandlerMiddleware($middleware);
        }

        if (! $middleware instanceof MiddlewareInterface) {
            throw Exception\InvalidMiddlewareException::forMiddlewareService($id, $middleware);
        }

        return $middleware;
    }
}
