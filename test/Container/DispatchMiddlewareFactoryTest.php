<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\DispatchMiddlewareFactory;
use Mezzio\Router\DispatchMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class DispatchMiddlewareFactoryTest extends TestCase
{
    public function testFactoryProducesDispatchMiddleware()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new DispatchMiddlewareFactory();

        $middleware = $factory($container);

        $this->assertInstanceOf(DispatchMiddleware::class, $middleware);
    }
}
