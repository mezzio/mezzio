<?php

declare(strict_types=1);

namespace MezzioTest\Middleware;

use Mezzio\Exception\InvalidMiddlewareException;
use Mezzio\Middleware\LazyLoadingMiddleware;
use Mezzio\MiddlewareContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class LazyLoadingMiddlewareTest extends TestCase
{
    private MiddlewareContainer $container;

    private ContainerInterface&MockObject $innerContainer;

    private ServerRequestInterface&MockObject $request;

    private RequestHandlerInterface&MockObject $handler;

    public function setUp(): void
    {
        $this->innerContainer = $this->createMock(ContainerInterface::class);
        $this->container      = new MiddlewareContainer($this->innerContainer);
        $this->request        = $this->createMock(ServerRequestInterface::class);
        $this->handler        = $this->createMock(RequestHandlerInterface::class);
    }

    public function buildLazyLoadingMiddleware(string $middlewareName): LazyLoadingMiddleware
    {
        return new LazyLoadingMiddleware($this->container, $middlewareName);
    }

    public function testProcessesMiddlewarePulledFromContainer(): void
    {
        $response   = $this->createMock(ResponseInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($response);

        $this->innerContainer->method('has')->with('foo')->willReturn(true);
        $this->innerContainer->method('get')->with('foo')->willReturn($middleware);

        $lazyloader = $this->buildLazyLoadingMiddleware('foo');
        $this->assertSame(
            $response,
            $lazyloader->process($this->request, $this->handler)
        );
    }

    public function testDoesNotCatchContainerExceptions(): void
    {
        $exception = new InvalidMiddlewareException();
        $this->innerContainer->method('has')->with('foo')->willReturn(true);
        $this->innerContainer->method('get')->with('foo')->willThrowException($exception);

        $lazyloader = $this->buildLazyLoadingMiddleware('foo');
        $this->expectException(InvalidMiddlewareException::class);
        $lazyloader->process($this->request, $this->handler);
    }
}
