<?php

declare(strict_types=1);

namespace Mezzio;

use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Marshal middleware for use in the application.
 *
 * This interface provides a number of methods for preparing and returning
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
 */
interface MiddlewareFactoryInterface
{
    /**
     * @param string|array|callable|MiddlewareInterface|RequestHandlerInterface $middleware
     * @psalm-param MiddlewareParam $middleware
     * @throws Exception\InvalidMiddlewareException If argument is not one of
     *    the specified types.
     */
    public function prepare($middleware): MiddlewareInterface;

    /**
     * Decorate callable standards-signature middleware via a CallableMiddlewareDecorator.
     */
    public function callable(callable $middleware): MiddlewareInterface;

    /**
     * Decorate a RequestHandlerInterface as middleware via RequestHandlerMiddleware.
     */
    public function handler(RequestHandlerInterface $handler): MiddlewareInterface;

    /**
     * Create lazy loading middleware based on a service name.
     */
    public function lazy(string $middleware): MiddlewareInterface;

    /**
     * Create a middleware pipeline from an array of middleware.
     *
     * This method allows passing an array of middleware as either:
     *
     * - discrete arguments
     * - an array of middleware, using the splat operator: pipeline(...$array)
     * - an array of middleware as the sole argument: pipeline($array)
     *
     * Each item is passed to prepare() before being passed to the
     * MiddlewarePipe instance the method returns.
     *
     * @param string|array|callable|MiddlewareInterface|RequestHandlerInterface ...$middleware
     * @psalm-param MiddlewareParam ...$middleware
     */
    public function pipeline(...$middleware): MiddlewarePipeInterface;
}
