<?php

declare(strict_types=1);

namespace MezzioTest\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use InvalidArgumentException;
use Mezzio\Middleware\WhoopsErrorResponseGenerator;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
use stdClass;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\RunInterface;

use function method_exists;

class WhoopsErrorResponseGeneratorTest extends TestCase
{
    /** @var RunInterface&MockObject */
    private $whoops;

    /** @var ServerRequestInterface&MockObject */
    private $request;

    /** @var ResponseInterface&MockObject */
    private $response;

    /** @var StreamInterface&MockObject */
    private $stream;

    public function setUp(): void
    {
        $this->whoops   = $this->createMock(RunInterface::class);
        $this->request  = $this->createMock(ServerRequestInterface::class);
        $this->response = $this->createMock(ResponseInterface::class);
        $this->stream   = $this->createMock(StreamInterface::class);
    }

    public function testWritesResultsOfWhoopsExceptionsHandlingToResponse(): void
    {
        $error          = new RuntimeException();
        $sendOutputFlag = true;

        $this->whoops->method('getHandlers')->willReturn([]);
        $this->whoops->method('handleException')->with($error)->willReturn('WHOOPS');
        $this->whoops->expects(self::exactly(3))
            ->method('writeToOutput')
            ->withConsecutive([], [false], [$sendOutputFlag])
            ->willReturn($sendOutputFlag);

        // Could do more assertions here, but these will be sufficient for
        // ensuring that the method for injecting metadata is never called.
        $this->request->expects(self::never())->method('getAttribute');

        $this->response->method('withStatus')
            ->with(StatusCode::STATUS_INTERNAL_SERVER_ERROR)
            ->willReturn($this->response);

        $this->response->method('getBody')->willReturn($this->stream);
        $this->response->method('getStatusCode')->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);

        $this->stream->method('write')->with('WHOOPS');

        $generator = new WhoopsErrorResponseGenerator($this->whoops);

        $this->assertSame(
            $this->response,
            $generator($error, $this->request, $this->response)
        );
    }

    public function testAddsRequestMetadataToWhoopsPrettyPageHandler(): void
    {
        $error          = new RuntimeException(
            'STATUS_INTERNAL_SERVER_ERROR',
            StatusCode::STATUS_INTERNAL_SERVER_ERROR
        );
        $sendOutputFlag = true;

        $handler = $this->createMock(PrettyPageHandler::class);
        $handler
            ->expects(self::once())
            ->method('addDataTable')
            ->with('Mezzio Application Request', [
                'HTTP Method'            => 'POST',
                'URI'                    => 'https://example.com/foo',
                'Script'                 => __FILE__,
                'Headers'                => [],
                'Cookies'                => [],
                'Attributes'             => [],
                'Query String Arguments' => [],
                'Body Params'            => [],
            ]);

        $this->whoops->method('getHandlers')->willReturn([$handler]);
        $this->whoops->method('handleException')->with($error)->willReturn('WHOOPS');
        $this->whoops->expects(self::exactly(3))
            ->method('writeToOutput')
            ->withConsecutive([], [false], [$sendOutputFlag])
            ->willReturn($sendOutputFlag);

        $this->request->method('getAttribute')
            ->withConsecutive(['originalUri', false], ['originalRequest', false])
            ->willReturn('https://example.com/foo', $this->request);

        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getServerParams')->willReturn(['SCRIPT_NAME' => __FILE__]);

        $this->response->method('withStatus')
            ->with(StatusCode::STATUS_INTERNAL_SERVER_ERROR)
            ->willReturn($this->response);
        $this->response->method('getStatusCode')->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);
        $this->response->method('getBody')->willReturn($this->stream);
        $this->request->method('getHeaders')->willReturn([]);
        $this->request->method('getCookieParams')->willReturn([]);
        $this->request->method('getAttributes')->willReturn([]);
        $this->request->method('getQueryParams')->willReturn([]);
        $this->request->method('getParsedBody')->willReturn([]);

        $this->stream->method('write')->with('WHOOPS');

        $generator = new WhoopsErrorResponseGenerator($this->whoops);

        $this->assertSame(
            $this->response,
            $generator($error, $this->request, $this->response)
        );
    }

    public function testJsonContentTypeResponseWithJsonResponseHandler(): void
    {
        $error      = new RuntimeException('STATUS_NOT_IMPLEMENTED', StatusCode::STATUS_NOT_IMPLEMENTED);
        $sendOutput = true;

        $handler = $this->createMock(JsonResponseHandler::class);

        if (method_exists(JsonResponseHandler::class, 'contentType')) {
            $handler->method('contentType')->willReturn('application/json');
        }

        $this->whoops->method('getHandlers')->willReturn([$handler]);
        $this->whoops->method('handleException')->with($error)->willReturn('error');
        $this->whoops->expects(self::exactly(3))
            ->method('writeToOutput')
            ->withConsecutive([], [false], [$sendOutput])
            ->willReturn($sendOutput);

        $this->request->method('getAttribute')
            ->withConsecutive(['originalUri', false], ['originalRequest', false])
            ->willReturn('https://example.com/foo', $this->request);

        $this->request->method('getMethod')->willReturn('POST');
        $this->request->method('getServerParams')->willReturn(['SCRIPT_NAME' => __FILE__]);
        $this->request->method('getHeaders')->willReturn([]);
        $this->request->method('getCookieParams')->willReturn([]);
        $this->request->method('getAttributes')->willReturn([]);
        $this->request->method('getQueryParams')->willReturn([]);
        $this->request->method('getParsedBody')->willReturn([]);

        $this->response->method('withHeader')->with('Content-Type', 'application/json')->willReturn($this->response);
        $this->response->method('withStatus')->with(StatusCode::STATUS_NOT_IMPLEMENTED)->willReturn($this->response);
        $this->response->method('getStatusCode')->willReturn(StatusCode::STATUS_NOT_IMPLEMENTED);
        $this->response->method('getBody')->willReturn($this->stream);

        $this->stream->method('write')->with('error');

        $generator = new WhoopsErrorResponseGenerator($this->whoops);

        $this->assertSame(
            $this->response,
            $generator($error, $this->request, $this->response)
        );
    }

    public function testThrowsInvalidArgumentExceptionOnNonRunForObject(): void
    {
        $whoops = new stdClass();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Mezzio\Middleware\WhoopsErrorResponseGenerator expects a Whoops\Run'
            . ' or Whoops\RunInterface instance; received stdClass'
        );

        new WhoopsErrorResponseGenerator($whoops);
    }

    public function testThrowsInvalidArgumentExceptionOnNonRunForScalar(): void
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
