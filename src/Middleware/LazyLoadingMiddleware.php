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
use Mezzio\Exception\InvalidMiddlewareException;
use Mezzio\IsCallableInteropMiddlewareTrait;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LazyLoadingMiddleware implements MiddlewareInterface
{
    use IsCallableInteropMiddlewareTrait;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $middlewareName;

    /**
     * @var ResponseInterface
     */
    private $responsePrototype;

    public function __construct(
        ContainerInterface $container,
        ResponseInterface $responsePrototype,
        string $middlewareName
    ) {
        $this->container = $container;
        $this->responsePrototype = $responsePrototype;
        $this->middlewareName = $middlewareName;
    }

    /**
     * @throws InvalidMiddlewareException for invalid middleware types pulled
     *     from the container.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $middleware = $this->container->get($this->middlewareName);

        // http-interop middleware
        if ($middleware instanceof MiddlewareInterface) {
            return $middleware->process($request, $handler);
        }

        // Unknown - invalid!
        if (! is_callable($middleware)) {
            throw new InvalidMiddlewareException(sprintf(
                'Lazy-loaded middleware "%s" is neither invokable nor implements %s',
                $this->middlewareName,
                MiddlewareInterface::class
            ));
        }

        // Callable http-interop middleware
        if ($this->isCallableInteropMiddleware($middleware)) {
            return $middleware($request, $handler);
        }

        // Legacy double-pass signature
        return $middleware($request, $this->responsePrototype, function ($request, $response) use ($handler) {
            return $handler->handle($request);
        });
    }
}
