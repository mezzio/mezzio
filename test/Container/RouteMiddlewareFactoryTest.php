<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\RouteMiddlewareFactory;
use Mezzio\Router\PathBasedRoutingMiddleware;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class RouteMiddlewareFactoryTest extends TestCase
{
    public function testFactoryProducesPathBasedRoutingMiddleware()
    {
        $router = $this->prophesize(RouterInterface::class)->reveal();
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(RouterInterface::class)->willReturn($router);
        $container->get(ResponseInterface::class)->willReturn($response);

        $factory = new RouteMiddlewareFactory();

        $middleware = $factory($container->reveal());

        $this->assertInstanceOf(PathBasedRoutingMiddleware::class, $middleware);
        $this->assertAttributeSame($router, 'router', $middleware);
        $this->assertAttributeSame($response, 'responsePrototype', $middleware);
    }
}
