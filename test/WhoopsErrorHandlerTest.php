<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest;

use Exception;
use Laminas\Diactoros\ServerRequest;
use Laminas\Stratigility\Http\Request as StratigilityRequest;
use Mezzio\WhoopsErrorHandler;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Whoops\Handler\Handler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

/**
 * @covers Mezzio\WhoopsErrorHandler
 */
class WhoopsErrorHandlerTest extends TestCase
{
    public function getPrettyPageHandler()
    {
        return $this->prophesize(PrettyPageHandler::class);
    }

    public function testInstantiationRequiresWhoopsAndPageHandler()
    {
        $whoops = new Whoops();
        $whoops->allowQuit(false);
        $pageHandler = $this->getPrettyPageHandler();

        $handler = new WhoopsErrorHandler($whoops, $pageHandler->reveal());
        $this->assertAttributeSame($whoops, 'whoops', $handler);
        $this->assertAttributeSame($pageHandler->reveal(), 'whoopsHandler', $handler);
    }

    public function testExceptionErrorPreparesPageHandlerAndInvokesWhoops()
    {
        $exception = new Exception('Boom!');

        $pageHandler = $this->getPrettyPageHandler();
        $pageHandler->addDataTable('Mezzio Application Request', Argument::type('array'))->shouldBeCalled();
        $pageHandler->setRun(Argument::any())->shouldBeCalled();
        $pageHandler->setInspector(Argument::any())->shouldBeCalled();
        $pageHandler->setException($exception)->shouldBeCalled();
        $pageHandler->handle(Argument::any())->willReturn(Handler::QUIT)->shouldBeCalled();

        $whoops = new Whoops();
        $whoops->allowQuit(false);

        $handler = new WhoopsErrorHandler($whoops, $pageHandler->reveal());

        $stream = $this->prophesize(StreamInterface::class);
        $stream->write('')->shouldBeCalled();

        $expected = $this->prophesize(ResponseInterface::class);
        $expected->getBody()->will(function () use ($stream) {
            return $stream->reveal();
        });

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->withStatus(500)->will(function () use ($expected) {
            return $expected->reveal();
        });

        $request = $this->prophesize(ServerRequestInterface::class);
        $request->getUri()->willReturn('http://example.com');
        $request->getMethod()->shouldBeCalled();
        $request->getServerParams()->willReturn(['SCRIPT_NAME' => __FILE__])->shouldBeCalled();
        $request->getHeaders()->shouldBeCalled();
        $request->getCookieParams()->shouldBeCalled();
        $request->getAttributes()->shouldBeCalled();
        $request->getQueryParams()->shouldBeCalled();
        $request->getParsedBody()->shouldBeCalled();
        $request->getAttribute('originalUri', false)->willReturn(false);
        $request->getAttribute('originalRequest', false)->willReturn(false);

        $result = $handler($request->reveal(), $response->reveal(), $exception);
        $this->assertSame($expected->reveal(), $result);
    }

    public function testOriginalRequestIsPulledFromStratigilityRequest()
    {
        $exception = new Exception('Boom!');

        $pageHandler = $this->getPrettyPageHandler();
        $pageHandler->addDataTable('Mezzio Application Request', Argument::type('array'))->shouldBeCalled();
        $pageHandler->setRun(Argument::any())->shouldBeCalled();
        $pageHandler->setInspector(Argument::any())->shouldBeCalled();
        $pageHandler->setException($exception)->shouldBeCalled();
        $pageHandler->handle(Argument::any())->willReturn(Handler::QUIT)->shouldBeCalled();

        $whoops  = new Whoops();
        $whoops->allowQuit(false);

        $handler = new WhoopsErrorHandler($whoops, $pageHandler->reveal());

        $stream = $this->prophesize(StreamInterface::class);
        $stream->write('')->shouldBeCalled();

        $expected = $this->prophesize(ResponseInterface::class);
        $expected->getBody()->will(function () use ($stream) {
            return $stream->reveal();
        });

        $response = $this->prophesize(ResponseInterface::class);
        $response->getStatusCode()->willReturn(200);
        $response->withStatus(500)->will(function () use ($expected) {
            return $expected->reveal();
        });

        $request           = new ServerRequest(['SCRIPT_NAME' => __FILE__]);
        $decoratingRequest = $this->prophesize(StratigilityRequest::class);
        $decoratingRequest->getAttribute('originalRequest', false)->willReturn($request);
        $decoratingRequest->getAttribute('originalUri', false)->willReturn($request->getUri());

        $result = $handler($decoratingRequest->reveal(), $response->reveal(), $exception);
        $this->assertSame($expected->reveal(), $result);
    }
}
