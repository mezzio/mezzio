<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\EmitterInterface;
use Laminas\Diactoros\ServerRequest;
use Mezzio\Application;
use Mezzio\Router\AuraRouter as AuraRouter;
use Mezzio\TemplatedErrorHandler;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;

class IntegrationTest extends TestCase
{
    public $response;

    public function setUp()
    {
        $this->response = null;
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
        $app      = new Application(new AuraRouter(), null, null, $this->getEmitter());
        $request  = new ServerRequest([], [], 'https://example.com/foo', 'GET');
        $response = new Response();

        $app->run($request, $response);
        $this->assertInstanceOf(ResponseInterface::class, $this->response);
        $this->assertEquals(404, $this->response->getStatusCode());
    }

    public function testInjectedFinalHandlerCanEmitA404WhenNoMiddlewareMatches()
    {
        $finalHandler = new TemplatedErrorHandler();
        $app          = new Application(new AuraRouter(), null, $finalHandler, $this->getEmitter());
        $request      = new ServerRequest([], [], 'https://example.com/foo', 'GET');
        $response     = new Response();

        $app->run($request, $response);
        $this->assertInstanceOf(ResponseInterface::class, $this->response);
        $this->assertEquals(404, $this->response->getStatusCode());
    }
}
