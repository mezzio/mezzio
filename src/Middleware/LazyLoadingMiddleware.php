<?php

declare(strict_types=1);

namespace Mezzio\Middleware;

use Mezzio\Exception\InvalidMiddlewareException;
use Mezzio\MiddlewareContainer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LazyLoadingMiddleware implements MiddlewareInterface
{
    public function __construct(private MiddlewareContainer $container, private string $middlewareName)
    {
    }

    /**
     * @throws InvalidMiddlewareException For invalid middleware types pulled
     *     from the container.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $middleware = $this->container->get($this->middlewareName);
        return $middleware->process($request, $handler);
    }
}
