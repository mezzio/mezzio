<?php

declare(strict_types=1);

namespace Mezzio;

use Laminas\Stratigility\MiddlewarePipeInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * The purpose of this class is to provide an existing class to the {@see \Mezzio\Container\ApplicationFactory}.
 * This factory fetches this class via the container to inject an instance of {@see MiddlewarePipeInterface} to the
 * {@see \Mezzio\Application}.
 *
 * @deprecated Will get superseded with v4.0.0. In 4.0.0, the {@see \Mezzio\Container\ApplicationFactory} will instead
 *             fetch the {@see MiddlewarePipeInterface} directly from the container.
 */
final class ApplicationPipeline implements MiddlewarePipeInterface
{
    private MiddlewarePipeInterface $pipeline;

    public function __construct(MiddlewarePipeInterface $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $this->pipeline->process($request, $handler);
    }

    public function pipe(MiddlewareInterface $middleware): void
    {
        $this->pipeline->pipe($middleware);
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return $this->pipeline->handle($request);
    }

    /**
     * To ensure that we are 100% compatible with the old implementation (direct middleware pipeline implementation)
     * we should handle a deep-clone here as well as {@see \Laminas\Stratigility\MiddlewarePipe::__clone} does.
     */
    public function __clone()
    {
        $this->pipeline = clone $this->pipeline;
    }
}
