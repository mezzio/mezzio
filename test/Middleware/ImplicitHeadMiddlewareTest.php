<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Middleware;

use Fig\Http\Message\RequestMethodInterface as RequestMethod;
use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Interop\Http\ServerMiddleware\DelegateInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Middleware\ImplicitHeadMiddleware;
use Mezzio\Router\Route;
use Mezzio\Router\RouteResult;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ImplicitHeadMiddlewareTest extends TestCase
{
    public function testReturnsResultOfNextOnNonHeadRequests()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_GET);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process($request->reveal())
            ->willReturn($response);

        $middleware = new ImplicitHeadMiddleware();
        $result = $middleware->process($request->reveal(), $delegate->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsResultOfNextWhenNoRouteResultPresentInRequest()
    {
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class, false)->willReturn(false);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process($request->reveal())
            ->willReturn($response);

        $middleware = new ImplicitHeadMiddleware();
        $result = $middleware->process($request->reveal(), $delegate->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsResultOfNextWhenRouteResultDoesNotComposeRoute()
    {
        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->willReturn(null);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class, false)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process($request->reveal())
            ->willReturn($response);

        $middleware = new ImplicitHeadMiddleware();
        $result = $middleware->process($request->reveal(), $delegate->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsResultOfNextWhenRouteSupportsHeadExplicitly()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitHead()->willReturn(false);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class, false)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process($request->reveal())
            ->willReturn($response);

        $middleware = new ImplicitHeadMiddleware();
        $result = $middleware->process($request->reveal(), $delegate->reveal());

        $this->assertSame($response, $result);
    }

    public function testReturnsNewResponseWhenRouteImplicitlySupportsHeadAndDoesNotSupportGet()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitHead()->willReturn(true);
        $route->allowsMethod(RequestMethod::METHOD_GET)->willReturn(false);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class, false)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process($request->reveal())->shouldNotBeCalled($response);

        $middleware = new ImplicitHeadMiddleware();
        $result = $middleware->process($request->reveal(), $delegate->reveal());

        $this->assertInstanceOf(Response::class, $result);
        $this->assertEquals(StatusCode::STATUS_OK, $result->getStatusCode());
        $this->assertEquals('', (string) $result->getBody());
    }

    public function testReturnsComposedResponseWhenPresentWhenRouteImplicitlySupportsHeadAndDoesNotSupportGet()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitHead()->willReturn(true);
        $route->allowsMethod(RequestMethod::METHOD_GET)->willReturn(false);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getMethod()->willReturn(RequestMethod::METHOD_HEAD);
        $request->getAttribute(RouteResult::class, false)->will([$result, 'reveal']);

        $response = $this->prophesize(ResponseInterface::class)->reveal();

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process($request->reveal())->shouldNotBeCalled($response);

        $expected   = new Response();
        $middleware = new ImplicitHeadMiddleware($expected);
        $result     = $middleware->process($request->reveal(), $delegate->reveal());

        $this->assertSame($expected, $result);
    }

    public function testInvokesNextWhenRouteImplicitlySupportsHeadAndSupportsGet()
    {
        $route = $this->prophesize(Route::class);
        $route->implicitHead()->willReturn(true);
        $route->allowsMethod(RequestMethod::METHOD_GET)->willReturn(true);

        $result = $this->prophesize(RouteResult::class);
        $result->getMatchedRoute()->will([$route, 'reveal']);

        $request = (new ServerRequest([], [], null, RequestMethod::METHOD_HEAD))
                ->withAttribute(RouteResult::class, $result->reveal());

        $response = new Response\JsonResponse(['some_data' => true], 400);

        $delegate = $this->prophesize(DelegateInterface::class);
        $delegate->process(Argument::that(function (ServerRequestInterface $request) {
            $attr = $request->getAttribute(ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE);
            $this->assertSame('HEAD', $attr);
            return true;
        }))->willReturn($response);

        $middleware = new ImplicitHeadMiddleware();
        $result = $middleware->process($request, $delegate->reveal());

        $this->assertSame(400, $result->getStatusCode());
        $this->assertSame('', (string) $result->getBody());
        $this->assertSame('application/json', $result->getHeaderLine('content-type'));
    }
}
