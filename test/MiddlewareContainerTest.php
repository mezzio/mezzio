<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest;

use Laminas\Stratigility\Middleware\RequestHandlerMiddleware;
use Mezzio\Exception;
use Mezzio\MiddlewareContainer;
use Mezzio\Router\Middleware\DispatchMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class MiddlewareContainerTest extends TestCase
{
    public function setUp(): void
    {
        $this->originContainer = $this->prophesize(ContainerInterface::class);
        $this->container = new MiddlewareContainer($this->originContainer->reveal());
    }

    public function testHasReturnsTrueIfOriginContainerHasService()
    {
        $this->originContainer->has('foo')->willReturn(true);
        $this->assertTrue($this->container->has('foo'));
    }

    public function testHasReturnsTrueIfOriginContainerDoesNotHaveServiceButClassExists()
    {
        $this->originContainer->has(__CLASS__)->willReturn(false);
        $this->assertTrue($this->container->has(__CLASS__));
    }

    public function testHasReturnsFalseIfOriginContainerDoesNotHaveServiceAndClassDoesNotExist()
    {
        $this->originContainer->has('not-a-class')->willReturn(false);
        $this->assertFalse($this->container->has('not-a-class'));
    }

    public function testGetRaisesExceptionIfServiceIsUnknown()
    {
        $this->originContainer->has('not-a-service')->willReturn(false);

        $this->expectException(Exception\MissingDependencyException::class);
        $this->container->get('not-a-service');
    }

    public function testGetRaisesExceptionIfServiceSpecifiedDoesNotImplementMiddlewareInterface()
    {
        $this->originContainer->has(__CLASS__)->willReturn(true);
        $this->originContainer->get(__CLASS__)->willReturn($this);

        $this->expectException(Exception\InvalidMiddlewareException::class);
        $this->container->get(__CLASS__);
    }

    public function testGetRaisesExceptionIfClassSpecifiedDoesNotImplementMiddlewareInterface()
    {
        $this->originContainer->has(__CLASS__)->willReturn(false);
        $this->originContainer->get(__CLASS__)->shouldNotBeCalled();

        $this->expectException(Exception\InvalidMiddlewareException::class);
        $this->container->get(__CLASS__);
    }

    public function testGetReturnsServiceFromOriginContainer()
    {
        $middleware = $this->prophesize(MiddlewareInterface::class)->reveal();
        $this->originContainer->has('middleware-service')->willReturn(true);
        $this->originContainer->get('middleware-service')->willReturn($middleware);

        $this->assertSame($middleware, $this->container->get('middleware-service'));
    }

    public function testGetReturnsInstantiatedClass()
    {
        $this->originContainer->has(DispatchMiddleware::class)->willReturn(false);
        $this->originContainer->get(DispatchMiddleware::class)->shouldNotBeCalled();

        $middleware = $this->container->get(DispatchMiddleware::class);
        $this->assertInstanceOf(DispatchMiddleware::class, $middleware);
    }

    public function testGetWillDecorateARequestHandlerAsMiddleware()
    {
        $handler = $this->prophesize(RequestHandlerInterface::class)->reveal();

        $this->originContainer->has('AHandlerNotMiddleware')->willReturn(true);
        $this->originContainer->get('AHandlerNotMiddleware')->willReturn($handler);

        $middleware = $this->container->get('AHandlerNotMiddleware');

        // Test that we get back middleware decorating the handler
        $this->assertInstanceOf(RequestHandlerMiddleware::class, $middleware);
        $this->assertAttributeSame($handler, 'handler', $middleware);
    }

    /**
     * @see https://github.com/zendframework/zend-expressive/issues/645
     */
    public function testGetDoesNotCastMiddlewareImplementingRequestHandlerToRequestHandlerMiddleware()
    {
        $pipeline = $this->prophesize(RequestHandlerInterface::class);
        $pipeline->willImplement(MiddlewareInterface::class);

        $this->originContainer->has('pipeline')->willReturn(true);
        $this->originContainer->get('pipeline')->will([$pipeline, 'reveal']);

        $middleware = $this->container->get('pipeline');

        $this->assertSame($middleware, $pipeline->reveal());
    }
}
