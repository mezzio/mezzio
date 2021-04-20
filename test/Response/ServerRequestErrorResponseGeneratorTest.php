<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Response;

use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

use function preg_match;
use function strpos;

class ServerRequestErrorResponseGeneratorTest extends TestCase
{
    /** @var TemplateRendererInterface&MockObject */
    private $renderer;

    /** @var ResponseInterface&MockObject */
    private $response;

    /** @var callable */
    private $responseFactory;

    public function setUp() : void
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->responseFactory = function (): ResponseInterface {
            return $this->response;
        };

        $this->renderer = $this->createMock(TemplateRendererInterface::class);
    }

    public function testPreparesTemplatedResponseWhenRendererPresent() : void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('write')->with('data from template');

        $this->response->method('withStatus')->with(422)->willReturn($this->response);
        $this->response->method('getBody')->willReturn($stream);
        $this->response->method('getStatusCode')->willReturn(422);
        $this->response->method('getReasonPhrase')->willReturn('Unexpected entity');

        $template = 'some::template';
        $e = new RuntimeException('This is the exception message', 422);
        $this->renderer
            ->method('render')
            ->with($template, [
                'response' => $this->response,
                'status'   => 422,
                'reason'   => 'Unexpected entity',
                'error'    => $e,
            ])
            ->willReturn('data from template');

        $generator = new ServerRequestErrorResponseGenerator(
            $this->responseFactory,
            true,
            $this->renderer,
            $template
        );

        $this->assertSame($this->response, $generator($e));
    }

    public function testPreparesResponseWithDefaultMessageOnlyWhenNoRendererPresentAndNotInDebugMode() : void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream->method('write')->with('An unexpected error occurred');

        $this->response->method('withStatus')->with(422)->willReturn($this->response);
        $this->response->method('getBody')->willReturn($stream);
        $this->response->expects(self::never())->method('getStatusCode');
        $this->response->expects(self::never())->method('getReasonPhrase');

        $e = new RuntimeException('This is the exception message', 422);

        $generator = new ServerRequestErrorResponseGenerator($this->responseFactory);

        $this->assertSame($this->response, $generator($e));
    }

    public function testPreparesResponseWithDefaultMessageAndStackTraceWhenNoRendererPresentAndInDebugMode() : void
    {
        $stream = $this->createMock(StreamInterface::class);
        $stream
            ->method('write')
            ->with(self::callback(function ($message) {
                self::assertMatchesRegularExpression('/^An unexpected error occurred; stack trace:/', $message);
                self::assertStringContainsString('Stack Trace:', $message);

                return true;
            }));

        $this->response->method('withStatus')->with(422)->willReturn($this->response);
        $this->response->method('getBody')->willReturn($stream);
        $this->response->expects(self::never())->method('getStatusCode');
        $this->response->expects(self::never())->method('getReasonPhrase');

        $e = new RuntimeException('This is the exception message', 422);

        $generator = new ServerRequestErrorResponseGenerator($this->responseFactory, true);

        $this->assertSame($this->response, $generator($e));
    }
}
