<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Middleware;

use Mezzio\Exception\InvalidMiddlewareException;
use Mezzio\Middleware\LazyLoadingMiddleware;
use Mezzio\MiddlewareContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LazyLoadingMiddlewareTest extends TestCase
{
    /** @var MiddlewareContainer&MockObject */
    private $container;

    /** @var ServerRequestInterface&MockObject */
    private $request;

    /** @var RequestHandlerInterface&MockObject */
    private $handler;

    public function setUp() : void
    {
        $this->container = $this->createMock(MiddlewareContainer::class);
        $this->request   = $this->createMock(ServerRequestInterface::class);
        $this->handler   = $this->createMock(RequestHandlerInterface::class);
    }

    public function buildLazyLoadingMiddleware($middlewareName) : LazyLoadingMiddleware
    {
        return new LazyLoadingMiddleware($this->container, $middlewareName);
    }

    public function testProcessesMiddlewarePulledFromContainer() : void
    {
        $response = $this->createMock(ResponseInterface::class);
        $middleware = $this->createMock(MiddlewareInterface::class);
        $middleware
            ->method('process')
            ->with($this->request, $this->handler)
            ->willReturn($response);

        $this->container->method('get')->with('foo')->willReturn($middleware);

        $lazyloader = $this->buildLazyLoadingMiddleware('foo');
        $this->assertSame(
            $response,
            $lazyloader->process($this->request, $this->handler)
        );
    }

    public function testDoesNotCatchContainerExceptions() : void
    {
        $exception = new InvalidMiddlewareException();
        $this->container->method('get')->with('foo')->willThrowException($exception);

        $lazyloader = $this->buildLazyLoadingMiddleware('foo');
        $this->expectException(InvalidMiddlewareException::class);
        $lazyloader->process($this->request, $this->handler);
    }
}
