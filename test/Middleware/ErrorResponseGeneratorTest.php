<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Middleware;

use Fig\Http\Message\StatusCodeInterface as StatusCode;
use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

class ErrorResponseGeneratorTest extends TestCase
{
    /** @var ServerRequestInterface|ObjectProphecy */
    private $request;

    /** @var StreamInterface|ObjectProphecy */
    private $stream;

    /** @var TemplateRendererInterface|ObjectProphecy */
    private $renderer;

    public function setUp(): void
    {
        $this->request  = $this->prophesize(ServerRequestInterface::class);
        $this->stream   = $this->prophesize(StreamInterface::class);
        $this->renderer = $this->prophesize(TemplateRendererInterface::class);
    }

    public function testWritesGenericMessageToResponseWhenNoRendererPresentAndNotInDebugMode()
    {
        $error = new RuntimeException('', 0);

        $initialResponse   = $this->prophesize(ResponseInterface::class);
        $secondaryResponse = $this->prophesize(ResponseInterface::class);

        $secondaryResponse->getBody()->will([$this->stream, 'reveal']);

        $initialResponse
            ->getStatusCode()
            ->willReturn(StatusCode::STATUS_OK);
        $initialResponse
            ->withStatus(StatusCode::STATUS_INTERNAL_SERVER_ERROR)
            ->will(function () use ($secondaryResponse) {
                $secondaryResponse->getStatusCode()->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);
                $secondaryResponse->getReasonPhrase()->willReturn('Network Connect Timeout Error');
                return $secondaryResponse->reveal();
            });

        $this->stream->write('An unexpected error occurred')->shouldBeCalled();

        $generator = new ErrorResponseGenerator();
        $response = $generator($error, $this->request->reveal(), $initialResponse->reveal());

        $this->assertSame($response, $secondaryResponse->reveal());
    }

    public function testWritesStackTraceToResponseWhenNoRendererPresentInDebugMode()
    {
        $leaf   = new RuntimeException('leaf', 415);
        $branch = new RuntimeException('branch', 0, $leaf);
        $error  = new RuntimeException('root', 599, $branch);

        $initialResponse   = $this->prophesize(ResponseInterface::class);
        $secondaryResponse = $this->prophesize(ResponseInterface::class);

        $secondaryResponse->getBody()->will([$this->stream, 'reveal']);

        $initialResponse
            ->getStatusCode()
            ->willReturn(StatusCode::STATUS_OK);
        $initialResponse
            ->withStatus(599)
            ->will(function () use ($secondaryResponse) {
                $secondaryResponse->getStatusCode()->willReturn(599);
                $secondaryResponse->getReasonPhrase()->willReturn('Network Connect Timeout Error');
                return $secondaryResponse->reveal();
            });

        $this->stream
            ->write(Argument::that(function ($body) use ($leaf, $branch, $error) {
                $this->assertContains($leaf->getTraceAsString(), $body);
                $this->assertContains($branch->getTraceAsString(), $body);
                $this->assertContains($error->getTraceAsString(), $body);
                return true;
            }))
            ->shouldBeCalled();

        $generator = new ErrorResponseGenerator($debug = true);
        $response = $generator($error, $this->request->reveal(), $initialResponse->reveal());

        $this->assertSame($response, $secondaryResponse->reveal());
    }

    public function templates()
    {
        return [
            'default' => [null, 'error::error'],
            'custom' => ['error::custom', 'error::custom'],
        ];
    }

    /**
     * @dataProvider templates
     *
     * @param null|string $template
     * @param string $expected
     */
    public function testRendersTemplateWithoutErrorDetailsWhenRendererPresentAndNotInDebugMode($template, $expected)
    {
        $error = new RuntimeException('', 0);

        $initialResponse   = $this->prophesize(ResponseInterface::class);
        $secondaryResponse = $this->prophesize(ResponseInterface::class);

        $this->renderer
            ->render($expected, [
                'response' => $secondaryResponse->reveal(),
                'request'  => $this->request->reveal(),
                'uri'      => 'https://example.com/foo',
                'status'   => StatusCode::STATUS_INTERNAL_SERVER_ERROR,
                'reason'   => 'Internal Server Error',
                'layout'   => 'layout::default',
            ])
            ->willReturn('TEMPLATED CONTENTS');

        $secondaryResponse->getBody()->will([$this->stream, 'reveal']);

        $initialResponse
            ->getStatusCode()
            ->willReturn(StatusCode::STATUS_OK);
        $initialResponse
            ->withStatus(StatusCode::STATUS_INTERNAL_SERVER_ERROR)
            ->will(function () use ($secondaryResponse) {
                $secondaryResponse->getStatusCode()->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);
                $secondaryResponse->getReasonPhrase()->willReturn('Internal Server Error');
                return $secondaryResponse->reveal();
            });

        $this->stream->write('TEMPLATED CONTENTS')->shouldBeCalled();

        $this->request->getUri()->willReturn('https://example.com/foo');

        $generator = $template
            ? new ErrorResponseGenerator(false, $this->renderer->reveal(), $template)
            : new ErrorResponseGenerator(false, $this->renderer->reveal());

        $response = $generator($error, $this->request->reveal(), $initialResponse->reveal());

        $this->assertSame($response, $secondaryResponse->reveal());
    }

    /**
     * @dataProvider templates
     *
     * @param null|string $template
     * @param string $expected
     */
    public function testRendersTemplateWithErrorDetailsWhenRendererPresentAndInDebugMode($template, $expected)
    {
        $error = new RuntimeException('', 0);

        $initialResponse   = $this->prophesize(ResponseInterface::class);
        $secondaryResponse = $this->prophesize(ResponseInterface::class);

        $secondaryResponse->getBody()->will([$this->stream, 'reveal']);

        $initialResponse
            ->getStatusCode()
            ->willReturn(StatusCode::STATUS_OK);
        $initialResponse
            ->withStatus(StatusCode::STATUS_INTERNAL_SERVER_ERROR)
            ->will(function () use ($secondaryResponse) {
                $secondaryResponse->getStatusCode()->willReturn(StatusCode::STATUS_INTERNAL_SERVER_ERROR);
                $secondaryResponse->getReasonPhrase()->willReturn('Network Connect Timeout Error');
                return $secondaryResponse->reveal();
            });

        $this->renderer
            ->render($expected, [
                'response' => $secondaryResponse->reveal(),
                'request'  => $this->request->reveal(),
                'uri'      => 'https://example.com/foo',
                'status'   => StatusCode::STATUS_INTERNAL_SERVER_ERROR,
                'reason'   => 'Network Connect Timeout Error',
                'error'    => $error,
                'layout'   => 'layout::default',
            ])
            ->willReturn('TEMPLATED CONTENTS');

        $this->stream->write('TEMPLATED CONTENTS')->shouldBeCalled();

        $this->request->getUri()->willReturn('https://example.com/foo');

        $generator = $template
            ? new ErrorResponseGenerator(true, $this->renderer->reveal(), $template)
            : new ErrorResponseGenerator(true, $this->renderer->reveal());

        $response = $generator($error, $this->request->reveal(), $initialResponse->reveal());

        $this->assertSame($response, $secondaryResponse->reveal());
    }
}
