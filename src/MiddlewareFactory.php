<?php

declare(strict_types=1);

namespace Mezzio;

use Laminas\Stratigility\Middleware\CallableMiddlewareDecorator;
use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Laminas\Stratigility\MiddlewarePipe;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

use function array_shift;
use function count;
use function is_array;
use function is_callable;
use function is_string;

/**
 * Marshal middleware for use in the application.
 *
 * This class provides a number of methods for preparing and returning
 * middleware for use within an application.
 *
 * If any middleware provided is already a MiddlewareInterface, it can be used
 * verbatim or decorated as-is. Other middleware types acceptable are:
 *
 * - PSR-15 RequestHandlerInterface instances; these will be decorated as
 *   RequestHandlerMiddleware instances.
 * - string service names resolving to middleware
 * - arrays of service names and/or MiddlewareInterface instances
 * - PHP callables that follow the PSR-15 signature
 *
 * Additionally, the class provides the following decorator/utility methods:
 *
 * - callable() will decorate the callable middleware passed to it using
 *   CallableMiddlewareDecorator.
 * - handler() will decorate the request handler passed to it using
 *   RequestHandlerMiddleware.
 * - lazy() will decorate the string service name passed to it, along with the
 *   factory instance, as a LazyLoadingMiddleware instance.
 * - pipeline() will create a MiddlewarePipe instance from the array of
 *   middleware passed to it, after passing each first to prepare().
 *
 * @psalm-type InterfaceType = RequestHandlerInterface|RequestHandlerMiddleware|MiddlewareInterface
 * @psalm-type CallableType = callable(ServerRequestInterface, RequestHandlerInterface): ResponseInterface
 * @psalm-type MiddlewareParam = string|InterfaceType|CallableType|list<string|InterfaceType|CallableType>
 * @final
 */
class MiddlewareFactory implements MiddlewareFactoryInterface
{
    public function __construct(private MiddlewareContainer $container)
    {
    }

    /** @inheritDoc */
    public function prepare($middleware): MiddlewareInterface
    {
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware;
        }

        if ($middleware instanceof RequestHandlerInterface) {
            return $this->handler($middleware);
        }

        if (is_callable($middleware)) {
            return $this->callable($middleware);
        }

        if (is_array($middleware)) {
            return $this->pipeline(...$middleware);
        }

        /**
         * @psalm-suppress DocblockTypeContradiction Unless there are native types, we should not trust phpdoc
         */
        if (! is_string($middleware) || $middleware === '') {
            throw Exception\InvalidMiddlewareException::forMiddleware($middleware);
        }

        return $this->lazy($middleware);
    }

    /** @inheritDoc */
    public function callable(callable $middleware): CallableMiddlewareDecorator
    {
        return new CallableMiddlewareDecorator($middleware);
    }

    /** @inheritDoc */
    public function handler(RequestHandlerInterface $handler): RequestHandlerMiddleware
    {
        return new RequestHandlerMiddleware($handler);
    }

    /** @inheritDoc */
    public function lazy(string $middleware): Middleware\LazyLoadingMiddleware
    {
        return new Middleware\LazyLoadingMiddleware($this->container, $middleware);
    }

    /** @inheritDoc */
    public function pipeline(...$middleware): MiddlewarePipe
    {
        // Allow passing arrays of middleware or individual lists of middleware
        if (
            is_array($middleware[0])
            && count($middleware) === 1
        ) {
            $middleware = array_shift($middleware);
        }

        $pipeline = new MiddlewarePipe();
        foreach ($middleware as $m) {
            $pipeline->pipe($this->prepare($m));
        }
        return $pipeline;
    }
}
