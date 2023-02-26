<?php

declare(strict_types=1);

namespace MezzioTest;

use ArrayObject;
use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Mezzio\Exception;
use Mezzio\MiddlewareContainer;
use Mezzio\Router\Middleware\DispatchMiddleware;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use stdClass;

class MiddlewareContainerTest extends TestCase
{
    private MiddlewareContainer $container;

    private InMemoryContainer $originContainer;

    public function setUp(): void
    {
        $this->originContainer = new InMemoryContainer();
        $this->container       = new MiddlewareContainer($this->originContainer);
    }

    public function testHasReturnsTrueIfOriginContainerHasService(): void
    {
        $this->originContainer->set('foo', new stdClass());

        $this->assertTrue($this->container->has('foo'));
    }

    public function testHasReturnsTrueIfOriginContainerDoesNotHaveServiceButClassExists(): void
    {
        $this->assertTrue($this->container->has(self::class));
    }

    public function testHasReturnsFalseIfOriginContainerDoesNotHaveServiceAndClassDoesNotExist(): void
    {
        $this->assertFalse($this->container->has('not-a-class'));
    }

    public function testGetRaisesExceptionIfServiceIsUnknown(): void
    {
        $this->expectException(Exception\MissingDependencyException::class);
        $this->container->get('not-a-service');
    }

    public function testGetRaisesExceptionIfServiceSpecifiedDoesNotImplementMiddlewareInterface(): void
    {
        $this->originContainer->set(ArrayObject::class, new ArrayObject());

        $this->expectException(Exception\InvalidMiddlewareException::class);
        $this->container->get(ArrayObject::class);
    }

    public function testGetRaisesExceptionIfClassSpecifiedDoesNotImplementMiddlewareInterface(): void
    {
        $this->expectException(Exception\InvalidMiddlewareException::class);
        $this->container->get(ArrayObject::class);
    }

    public function testGetReturnsServiceFromOriginContainer(): void
    {
        $middleware = $this->createMock(MiddlewareInterface::class);

        $this->originContainer->set('middleware-service', $middleware);

        $this->assertSame($middleware, $this->container->get('middleware-service'));
    }

    public function testGetReturnsInstantiatedClass(): void
    {
        $middleware = $this->container->get(DispatchMiddleware::class);
        $this->assertInstanceOf(DispatchMiddleware::class, $middleware);
    }

    public function testGetWillDecorateARequestHandlerAsMiddleware(): void
    {
        $handler = $this->createMock(RequestHandlerInterface::class);

        $this->originContainer->set('AHandlerNotMiddleware', $handler);

        $middleware = $this->container->get('AHandlerNotMiddleware');

        self::assertEquals(new RequestHandlerMiddleware($handler), $middleware);
    }

    /**
     * @see https://github.com/zendframework/zend-expressive/issues/645
     */
    public function testGetDoesNotCastMiddlewareImplementingRequestHandlerToRequestHandlerMiddleware(): void
    {
        $pipeline = $this->createMock(MiddlewareAndRequestHandlerInterface::class);

        $this->originContainer->set('pipeline', $pipeline);

        $this->assertSame($pipeline, $this->container->get('pipeline'));
    }
}
