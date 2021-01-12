<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use InvalidArgumentException;
use Mezzio\Middleware\WhoopsErrorResponseGenerator;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use stdClass;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run;
use Whoops\RunInterface;

use function interface_exists;
use function method_exists;

class WhoopsErrorResponseGeneratorTest extends TestCase
{
    use ProphecyTrait;

    /** @var Run|RunInterface|ObjectProphecy */
    private $whoops;

    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    /** @var StreamInterface|ObjectProphecy */
    private $stream;

    public function setUp(): void
    {
        // Run is marked final in 2.X, but in that version, we can mock the
        // RunInterface. 1.X has only Run, and it is not final.
        $this->whoops = interface_exists(RunInterface::class)
            ? $this->prophesize(RunInterface::class)
            : $this->prophesize(Run::class);

        $this->request  = $this->prophesize(ServerRequestInterface::class);
        $this->response = $this->prophesize(ResponseInterface::class);
        $this->stream   = $this->prophesize(StreamInterface::class);
    }

    public function testWritesResultsOfWhoopsExceptionsHandlingToResponse()
    {
        $error = new RuntimeException();
        $sendOutputFlag = true;

        $this->whoops->getHandlers()->willReturn([]);
        $this->whoops->handleException($error)->willReturn('WHOOPS');
        $this->whoops->writeToOutput()->willReturn($sendOutputFlag);
        $this->whoops->writeToOutput(false)->shouldBeCalled();
        $this->whoops->writeToOutput($sendOutputFlag)->shouldBeCalled();

        // Could do more assertions here, but these will be sufficent for
        // ensuring that the method for injecting metadata is never called.
        $this->request->getAttribute('originalUri', false)->shouldNotBeCalled();
        $this->request->getAttribute('originalRequest', false)->shouldNotBeCalled();

        $this->response->withStatus(StatusCode::STATUS_INTERNAL_SERVER_ERROR)->will([$this->response, 'reveal']);
        $this->response->getBody()->will([$this->stream, 'reveal']);
        $this->response->getStatusCode()->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);

        $this->stream->write('WHOOPS')->shouldBeCalled();

        $generator = new WhoopsErrorResponseGenerator($this->whoops->reveal());

        $this->assertSame(
            $this->response->reveal(),
            $generator($error, $this->request->reveal(), $this->response->reveal())
        );
    }

    public function testAddsRequestMetadataToWhoopsPrettyPageHandler()
    {
        $error = new RuntimeException('STATUS_INTERNAL_SERVER_ERROR', StatusCode::STATUS_INTERNAL_SERVER_ERROR);
        $sendOutputFlag = true;

        $handler = $this->prophesize(PrettyPageHandler::class);
        $handler
            ->addDataTable('Mezzio Application Request', [
                'HTTP Method'            => 'POST',
                'URI'                    => 'https://example.com/foo',
                'Script'                 => __FILE__,
                'Headers'                => [],
                'Cookies'                => [],
                'Attributes'             => [],
                'Query String Arguments' => [],
                'Body Params'            => [],
            ])
            ->shouldBeCalled();

        $this->whoops->getHandlers()->willReturn([$handler->reveal()]);
        $this->whoops->handleException($error)->willReturn('WHOOPS');
        $this->whoops->writeToOutput()->willReturn($sendOutputFlag);
        $this->whoops->writeToOutput(false)->shouldBeCalled();
        $this->whoops->writeToOutput($sendOutputFlag)->shouldBeCalled();

        $this->request->getAttribute('originalUri', false)->willReturn('https://example.com/foo');
        $this->request->getAttribute('originalRequest', false)->will([$this->request, 'reveal']);
        $this->request->getMethod()->willReturn('POST');
        $this->request->getServerParams()->willReturn(['SCRIPT_NAME' => __FILE__]);
        $this->request->getHeaders()->willReturn([]);
        $this->request->getCookieParams()->willReturn([]);
        $this->request->getAttributes()->willReturn([]);
        $this->request->getQueryParams()->willReturn([]);
        $this->request->getParsedBody()->willReturn([]);

        $this->response->withStatus(StatusCode::STATUS_INTERNAL_SERVER_ERROR)->will([$this->response, 'reveal']);
        $this->response->getStatusCode()->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);
        $this->response->getBody()->will([$this->stream, 'reveal']);

        $this->stream->write('WHOOPS')->shouldBeCalled();

        $generator = new WhoopsErrorResponseGenerator($this->whoops->reveal());

        $this->assertSame(
            $this->response->reveal(),
            $generator($error, $this->request->reveal(), $this->response->reveal())
        );
    }

    public function testJsonContentTypeResponseWithJsonResponseHandler()
    {
        $error = new RuntimeException('STATUS_NOT_IMPLEMENTED', StatusCode::STATUS_NOT_IMPLEMENTED);
        $sendOutput = true;

        $handler = $this->prophesize(JsonResponseHandler::class);

        if (method_exists(JsonResponseHandler::class, 'contentType')) {
            $handler->contentType()->willReturn('application/json');
        }

        $this->whoops->getHandlers()->willReturn([$handler->reveal()]);
        $this->whoops->handleException($error)->willReturn('error');
        $this->whoops->writeToOutput()->willReturn($sendOutput);
        $this->whoops->writeToOutput(false)->shouldBeCalled();
        $this->whoops->writeToOutput($sendOutput)->shouldBeCalled();

        $this->request->getAttribute('originalUri', false)->willReturn('https://example.com/foo');
        $this->request->getAttribute('originalRequest', false)->will([$this->request, 'reveal']);
        $this->request->getMethod()->willReturn('POST');
        $this->request->getServerParams()->willReturn(['SCRIPT_NAME' => __FILE__]);
        $this->request->getHeaders()->willReturn([]);
        $this->request->getCookieParams()->willReturn([]);
        $this->request->getAttributes()->willReturn([]);
        $this->request->getQueryParams()->willReturn([]);
        $this->request->getParsedBody()->willReturn([]);

        $this->response->withHeader('Content-Type', 'application/json')->will([$this->response, 'reveal']);
        $this->response->withStatus(StatusCode::STATUS_NOT_IMPLEMENTED)->will([$this->response, 'reveal']);
        $this->response->getStatusCode()->willReturn(StatusCode::STATUS_NOT_IMPLEMENTED);
        $this->response->getBody()->will([$this->stream, 'reveal']);

        $this->stream->write('error')->shouldBeCalled();

        $generator = new WhoopsErrorResponseGenerator($this->whoops->reveal());

        $this->assertSame(
            $this->response->reveal(),
            $generator($error, $this->request->reveal(), $this->response->reveal())
        );
    }

    public function testThrowsInvalidArgumentExceptionOnNonRunForObject()
    {
        $whoops = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Mezzio\Middleware\WhoopsErrorResponseGenerator expects a Whoops\Run'
            . ' or Whoops\RunInterface instance; received stdClass'
        );

        new WhoopsErrorResponseGenerator($whoops);
    }

    public function testThrowsInvalidArgumentExceptionOnNonRunForScalar()
    {
        $whoops = 'foo';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Mezzio\Middleware\WhoopsErrorResponseGenerator expects a Whoops\Run'
            . ' or Whoops\RunInterface instance; received string'
        );

        new WhoopsErrorResponseGenerator($whoops);
    }
}
