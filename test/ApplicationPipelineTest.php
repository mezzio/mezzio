<?php

declare(strict_types=1);

namespace MezzioTest;

use Laminas\Stratigility\MiddlewarePipeInterface;
use Mezzio\ApplicationPipeline;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;

final class ApplicationPipelineTest extends TestCase
{
    private ApplicationPipeline $applicationPipeline;

    /** @var MiddlewarePipeInterface&MockObject */
    private MiddlewarePipeInterface $pipeline;

    protected function setUp(): void
    {
        parent::setUp();
        $this->pipeline            = $this->createMock(MiddlewarePipeInterface::class);
        $this->applicationPipeline = new ApplicationPipeline($this->pipeline);
    }

    public function testWillDeepClonePipeline(): void
    {
        $clonedApplicationPipeline = clone $this->applicationPipeline;
        $pipelineFromClone         = $this->extractPipeline($clonedApplicationPipeline);

        self::assertNotSame($pipelineFromClone, $this->pipeline);
    }

    public function testWillProxyProcessToPipeline(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $handler  = $this->createMock(RequestHandlerInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $this->pipeline
            ->expects(self::once())
            ->method('process')
            ->with($request, $handler)
            ->willReturn($response);

        self::assertSame($response, $this->applicationPipeline->process($request, $handler));
    }

    public function testWillProxyHandleToPipeline(): void
    {
        $request  = $this->createMock(ServerRequestInterface::class);
        $response = $this->createMock(ResponseInterface::class);

        $this->pipeline
            ->expects(self::once())
            ->method('handle')
            ->with($request)
            ->willReturn($response);

        self::assertSame($response, $this->applicationPipeline->handle($request));
    }

    public function testWillProxyPipeToPipeline(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $this->pipeline
            ->expects(self::once())
            ->method('pipe')
            ->with($middleware);

        $this->applicationPipeline->pipe($middleware);
    }

    private function extractPipeline(ApplicationPipeline $clonedApplicationPipeline): MiddlewarePipeInterface
    {
        $property = new ReflectionProperty(ApplicationPipeline::class, 'pipeline');
        $property->setAccessible(true);

        $pipe = $property->getValue($clonedApplicationPipeline);
        self::assertInstanceOf(MiddlewarePipeInterface::class, $pipe);

        return $pipe;
    }
}
