<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\NotFoundMiddlewareFactory;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Middleware\NotFoundMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class NotFoundMiddlewareFactoryTest extends TestCase
{
    public function testUsesComposedNotFoundHandlerServiceToCreateNotFoundHandlerMiddleware()
    {
        $handler   = $this->prophesize(NotFoundHandler::class)->reveal();
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(NotFoundHandler::class)->willReturn($handler);
        $factory = new NotFoundMiddlewareFactory();

        $middleware = $factory($container->reveal());

        $this->assertInstanceOf(NotFoundMiddleware::class, $middleware);
        $this->assertAttributeSame($handler, 'internalHandler', $middleware);
    }
}
