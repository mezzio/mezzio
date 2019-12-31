<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest;

use Laminas\Stratigility\Http\Response as StratigilityResponse;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\ErrorMiddlewarePipe;
use PHPUnit_Framework_TestCase as TestCase;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\UriInterface as Uri;

class ErrorMiddlewarePipeTest extends TestCase
{
    public function setUp()
    {
        $this->internalPipe = new MiddlewarePipe();
        $this->errorPipe = new ErrorMiddlewarePipe($this->internalPipe);
    }

    public function testWillDispatchErrorMiddlewareComposedInInternalPipeline()
    {
        $error = (object) ['error' => true];
        $triggered = (object) [
            'first' => false,
            'second' => false,
            'third' => false,
        ];

        $first = function ($err, $request, $response, $next) use ($error, $triggered) {
            $this->assertSame($error, $err);
            $triggered->first = true;
            return $next($request, $response, $err);
        };
        $second = function ($request, $response, $next) use ($triggered) {
            $triggered->second = true;
            return $next($request, $response);
        };
        $third = function ($err, $request, $response, $next) use ($error, $triggered) {
            $this->assertSame($error, $err);
            $triggered->third = true;
            return $response;
        };

        $this->internalPipe->pipe($first);
        $this->internalPipe->pipe($second);
        $this->internalPipe->pipe($third);

        $uri = $this->prophesize(Uri::class);
        $uri->getPath()->willReturn('/');
        $request = $this->prophesize(Request::class);
        $request->getUri()->willReturn($uri->reveal());

        // The following is required due to Stratigility decorating requests:
        $request->withAttribute('originalUri', $uri->reveal())->will(function () use ($request) {
            return $request->reveal();
        });

        $response = $this->prophesize(Response::class);

        $final = function ($request, $response, $err = null) {
            $this->fail('Final handler should not be triggered');
        };

        $result = $this->errorPipe->__invoke($error, $request->reveal(), $response->reveal(), $final);
        $this->assertInstanceOf(StratigilityResponse::class, $result);
        $this->assertSame($response->reveal(), $result->getOriginalResponse());
        $this->assertTrue($triggered->first);
        $this->assertFalse($triggered->second);
        $this->assertTrue($triggered->third);
    }
}