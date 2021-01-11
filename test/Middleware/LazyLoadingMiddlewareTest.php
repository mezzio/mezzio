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
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LazyLoadingMiddlewareTest extends TestCase
{
    /** @var MiddlewareContainer|ObjectProphecy */
    private $container;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    public function setUp(): void
    {
        $this->container = $this->prophesize(MiddlewareContainer::class);
        $this->request   = $this->prophesize(ServerRequestInterface::class);
        $this->handler   = $this->prophesize(RequestHandlerInterface::class);
    }

    public function buildLazyLoadingMiddleware($middlewareName)
    {
        return new LazyLoadingMiddleware(
            $this->container->reveal(),
            $middlewareName
        );
    }

    public function testProcessesMiddlewarePulledFromContainer()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $middleware = $this->prophesize(MiddlewareInterface::class);
        $middleware
            ->process(
                $this->request->reveal(),
                $this->handler->reveal()
            )->willReturn($response);

        $this->container->get('foo')->will([$middleware, 'reveal']);

        $lazyloader = $this->buildLazyLoadingMiddleware('foo');
        $this->assertSame(
            $response,
            $lazyloader->process($this->request->reveal(), $this->handler->reveal())
        );
    }

    public function testDoesNotCatchContainerExceptions()
    {
        $exception = new InvalidMiddlewareException();
        $this->container->get('foo')->willThrow($exception);

        $lazyloader = $this->buildLazyLoadingMiddleware('foo');
        $this->expectException(InvalidMiddlewareException::class);
        $lazyloader->process($this->request->reveal(), $this->handler->reveal());
    }
}
