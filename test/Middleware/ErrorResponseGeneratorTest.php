<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Laminas\Diactoros\Uri;
use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class ErrorResponseGeneratorTest extends TestCase
{
    /** @var ServerRequestInterface&MockObject */
    private $request;

    /** @var StreamInterface&MockObject */
    private $stream;

    /** @var TemplateRendererInterface&MockObject */
    private $renderer;

    public function setUp() : void
    {
        $this->request  = $this->createMock(ServerRequestInterface::class);
        $this->stream   = $this->createMock(StreamInterface::class);
        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testWritesGenericMessageToResponseWhenNoRendererPresentAndNotInDebugMode() : void
    {
        $error = new RuntimeException('', 0);

        $initialResponse   = $this->createMock(ResponseInterface::class);
        $secondaryResponse = $this->createMock(ResponseInterface::class);

        $secondaryResponse->method('getBody')->willReturn($this->stream);

        $initialResponse->method('getStatusCode')->willReturn(StatusCode::STATUS_OK);
        $initialResponse
            ->method('withStatus')
            ->with(StatusCode::STATUS_INTERNAL_SERVER_ERROR)
            ->willReturnCallback(function () use ($secondaryResponse) {
                $secondaryResponse->method('getStatusCode')->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);
                $secondaryResponse->method('getReasonPhrase')->willReturn('Network Connect Timeout Error');
                return $secondaryResponse;
            });

        $this->stream->expects(self::once())->method('write')->with('An unexpected error occurred');

        $generator = new ErrorResponseGenerator();
        $response = $generator($error, $this->request, $initialResponse);

        $this->assertSame($response, $secondaryResponse);
    }

    public function testWritesStackTraceToResponseWhenNoRendererPresentInDebugMode() : void
    {
        $leaf   = new RuntimeException('leaf', 415);
        $branch = new RuntimeException('branch', 0, $leaf);
        $error  = new RuntimeException('root', 599, $branch);

        $initialResponse   = $this->createMock(ResponseInterface::class);
        $secondaryResponse = $this->createMock(ResponseInterface::class);

        $secondaryResponse->method('getBody')->willReturn($this->stream);

        $initialResponse->method('getStatusCode')->willReturn(StatusCode::STATUS_OK);
        $initialResponse
            ->method('withStatus')
            ->with(599)
            ->willReturnCallback(function () use ($secondaryResponse) {
                $secondaryResponse->method('getStatusCode')->willReturn(599);
                $secondaryResponse->method('getReasonPhrase')->willReturn('Network Connect Timeout Error');
                return $secondaryResponse;
            });

        $this->stream->expects(self::once())
            ->method('write')
            ->with(self::callback(function ($body) use ($leaf, $branch, $error) {
                $this->assertContains($leaf->getTraceAsString(), $body);
                $this->assertContains($branch->getTraceAsString(), $body);
                $this->assertContains($error->getTraceAsString(), $body);
                return true;
            }));

        $generator = new ErrorResponseGenerator($debug = true);
        $response = $generator($error, $this->request, $initialResponse);

        $this->assertSame($response, $secondaryResponse);
    }

    public function templates() : array
    {
        return [
            'default' => [null, 'error::error'],
            'custom' => ['error::custom', 'error::custom'],
        ];
    }

    /**
     * @dataProvider templates
     */
    public function testRendersTemplateWithoutErrorDetailsWhenRendererPresentAndNotInDebugMode(
        ?string $template,
        string $expected
    ) : void {
        $error = new RuntimeException('', 0);

        $initialResponse   = $this->createMock(ResponseInterface::class);
        $secondaryResponse = $this->createMock(ResponseInterface::class);

        $this->renderer
            ->method('render')
            ->with($expected, [
                'response' => $secondaryResponse,
                'request'  => $this->request,
                'uri'      => 'https://example.com/foo',
                'status'   => StatusCode::STATUS_INTERNAL_SERVER_ERROR,
                'reason'   => 'Internal Server Error',
                'layout'   => 'layout::default',
            ])
            ->willReturn('TEMPLATED CONTENTS');

        $secondaryResponse->method('getBody')->willReturn($this->stream);

        $initialResponse->method('getStatusCode')->willReturn(StatusCode::STATUS_OK);
        $initialResponse
            ->method('withStatus')
            ->with(StatusCode::STATUS_INTERNAL_SERVER_ERROR)
            ->willReturnCallback(function () use ($secondaryResponse) {
                $secondaryResponse->method('getStatusCode')->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);
                $secondaryResponse->method('getReasonPhrase')->willReturn('Internal Server Error');
                return $secondaryResponse;
            });

        $this->stream->expects(self::once())
            ->method('write')
            ->with('TEMPLATED CONTENTS');

        $this->request->method('getUri')->willReturn(new Uri('https://example.com/foo'));

        $generator = $template
            ? new ErrorResponseGenerator(false, $this->renderer, $template)
            : new ErrorResponseGenerator(false, $this->renderer);

        $response = $generator($error, $this->request, $initialResponse);

        $this->assertSame($response, $secondaryResponse);
    }

    /**
     * @dataProvider templates
     */
    public function testRendersTemplateWithErrorDetailsWhenRendererPresentAndInDebugMode(
        ?string $template,
        string $expected
    ) : void {
        $error = new RuntimeException('', 0);

        $initialResponse   = $this->createMock(ResponseInterface::class);
        $secondaryResponse = $this->createMock(ResponseInterface::class);

        $secondaryResponse->method('getBody')->willReturn($this->stream);

        $initialResponse->method('getStatusCode')->willReturn(StatusCode::STATUS_OK);
        $initialResponse
            ->method('withStatus')
            ->with(StatusCode::STATUS_INTERNAL_SERVER_ERROR)
            ->willReturnCallback(function () use ($secondaryResponse) {
                $secondaryResponse->method('getStatusCode')->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);
                $secondaryResponse->method('getReasonPhrase')->willReturn('Network Connect Timeout Error');
                return $secondaryResponse;
            });

        $this->renderer
            ->method('render')
            ->with($expected, [
                'response' => $secondaryResponse,
                'request'  => $this->request,
                'uri'      => 'https://example.com/foo',
                'status'   => StatusCode::STATUS_INTERNAL_SERVER_ERROR,
                'reason'   => 'Network Connect Timeout Error',
                'error'    => $error,
                'layout'   => 'layout::default',
            ])
            ->willReturn('TEMPLATED CONTENTS');

        $this->stream->expects(self::once())
            ->method('write')
            ->with('TEMPLATED CONTENTS');

        $this->request->method('getUri')->willReturn(new Uri('https://example.com/foo'));

        $generator = $template
            ? new ErrorResponseGenerator(true, $this->renderer, $template)
            : new ErrorResponseGenerator(true, $this->renderer);

        $response = $generator($error, $this->request, $initialResponse);

        $this->assertSame($response, $secondaryResponse);
    }
}
