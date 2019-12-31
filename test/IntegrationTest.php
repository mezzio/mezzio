<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmitterInterface;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Application;
use Mezzio\Delegate\NotFoundDelegate;
use Mezzio\Router\FastRouteRouter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;

class IntegrationTest extends TestCase
{
    public $errorHandler;
    public $response;

    public function setUp()
    {
        $this->response = null;
        $this->errorHandler = null;
    }

    public function tearDown()
    {
        if ($this->errorHandler) {
            set_error_handler($this->errorHandler);
            $this->errorHandler = null;
        }
    }

    public function getEmitter()
    {
        $self    = $this;
        $emitter = $this->prophesize(EmitterInterface::class);
        $emitter
            ->emit(Argument::type(ResponseInterface::class))
            ->will(function ($args) use ($self) {
                $response = array_shift($args);
                $self->response = $response;
                return null;
            })
            ->shouldBeCalled();
        return $emitter->reveal();
    }

    public function testDefaultFinalHandlerCanEmitA404WhenNoMiddlewareMatches()
    {
        $app      = new Application(new FastRouteRouter(), null, null, $this->getEmitter());
        $request  = new ServerRequest([], [], 'https://example.com/foo', 'GET');
        $response = new Response();

        $app->run($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $this->response);
        $this->assertEquals(StatusCode::STATUS_NOT_FOUND, $this->response->getStatusCode());
    }

    public function testInjectedFinalHandlerCanEmitA404WhenNoMiddlewareMatches()
    {
        $request  = new ServerRequest([], [], 'https://example.com/foo', 'GET');
        $response = new Response();
        $delegate = new NotFoundDelegate($response);
        $app      = new Application(new FastRouteRouter(), null, $delegate, $this->getEmitter());

        $app->run($request, $response);

        $this->assertInstanceOf(ResponseInterface::class, $this->response);
        $this->assertEquals(StatusCode::STATUS_NOT_FOUND, $this->response->getStatusCode());
    }
}
