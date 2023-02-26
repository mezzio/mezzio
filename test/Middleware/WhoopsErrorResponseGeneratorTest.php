<?php

declare(strict_types=1);

namespace MezzioTest\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use InvalidArgumentException;
use Laminas\Diactoros\Response\TextResponse;
use Laminas\Diactoros\ServerRequest;
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
        $error = new RuntimeException();

        $this->whoops->method('getHandlers')->willReturn([]);
        $this->whoops->method('handleException')->with($error)->willReturn('WHOOPS');
        $this->whoops->expects(self::exactly(3))
            ->method('writeToOutput')
            ->willReturn(true);

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
        $error = new RuntimeException(
            'STATUS_INTERNAL_SERVER_ERROR',
            StatusCode::STATUS_INTERNAL_SERVER_ERROR
        );

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
            ->willReturn(true);

        $request = (new ServerRequest(
            ['SCRIPT_NAME' => __FILE__],
            [],
            'https://example.com/foo',
            'POST',
        ))->withParsedBody([])
        ->withoutHeader('Host');

        $response  = new TextResponse('Foo');
        $generator = new WhoopsErrorResponseGenerator($this->whoops);

        $errorResponse = $generator($error, $request, $response);
        self::assertNotSame($response, $errorResponse);
        self::assertSame(StatusCode::STATUS_INTERNAL_SERVER_ERROR, $errorResponse->getStatusCode());
        self::assertEquals('WHOOPS', (string) $errorResponse->getBody());
    }

    public function testJsonContentTypeResponseWithJsonResponseHandler(): void
    {
        $error = new RuntimeException('STATUS_NOT_IMPLEMENTED', StatusCode::STATUS_NOT_IMPLEMENTED);

        $handler = $this->createMock(JsonResponseHandler::class);

        if (method_exists(JsonResponseHandler::class, 'contentType')) {
            $handler->method('contentType')->willReturn('application/json');
        }

        $this->whoops->method('getHandlers')->willReturn([$handler]);
        $this->whoops->method('handleException')->with($error)->willReturn('error');
        $this->whoops->expects(self::exactly(3))
            ->method('writeToOutput')
            ->willReturn(true);

        $request = (new ServerRequest(
            ['SCRIPT_NAME' => __FILE__],
            [],
            null,
            'POST',
        ))->withAttribute('originalUrl', 'https://example.com/foo');

        $response = new TextResponse('Foo');

        $generator = new WhoopsErrorResponseGenerator($this->whoops);

        $errorResponse = $generator($error, $request, $response);

        self::assertNotSame($response, $errorResponse);
        self::assertSame(StatusCode::STATUS_NOT_IMPLEMENTED, $errorResponse->getStatusCode());
        self::assertSame('application/json', $errorResponse->getHeader('Content-Type')[0] ?? null);
        self::assertEquals('error', (string) $errorResponse->getBody());
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
