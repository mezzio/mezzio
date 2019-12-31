<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Middleware;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Mezzio\Middleware\DispatchMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class DispatchMiddlewareTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var RequestHandlerInterface|ObjectProphecy */
    private $handler;

    /** @var DispatchMiddleware */
    private $middleware;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var ResponseInterface|ObjectProphecy */
    private $responsePrototype;

    public function setUp()
    {
        $this->container         = $this->prophesize(ContainerInterface::class);
        $this->responsePrototype = $this->prophesize(ResponseInterface::class);
        $this->router            = $this->prophesize(RouterInterface::class);
        $this->request           = $this->prophesize(ServerRequestInterface::class);
        $this->handler          = $this->prophesize(RequestHandlerInterface::class);
        $this->middleware        = new DispatchMiddleware(
            $this->router->reveal(),
            $this->responsePrototype->reveal(),
            $this->container->reveal()
        );
    }

    public function testInvokesDelegateIfRequestDoesNotContainRouteResult()
    {
        $expected = $this->prophesize(ResponseInterface::class)->reveal();
        $this->request->getAttribute(RouteResult::class, false)->willReturn(false);
        $this->handler->handle($this->request->reveal())->willReturn($expected);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame($expected, $response);
    }

    public function testInvokesMatchedMiddlewareWhenRouteResult()
    {
        $this->handler->handle()->shouldNotBeCalled();

        $expected = $this->prophesize(ResponseInterface::class)->reveal();
        $routedMiddleware = $this->prophesize(MiddlewareInterface::class);
        $routedMiddleware
            ->process($this->request->reveal(), $this->handler->reveal())
            ->willReturn($expected);

        $routeResult = RouteResult::fromRoute(new Route('/', $routedMiddleware->reveal()));

        $this->request->getAttribute(RouteResult::class, false)->willReturn($routeResult);

        $response = $this->middleware->process($this->request->reveal(), $this->handler->reveal());

        $this->assertSame($expected, $response);
    }
}
