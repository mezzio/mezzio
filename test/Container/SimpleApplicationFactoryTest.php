<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\Diactoros\Response\TextResponse;
use Mezzio\Application;
use Mezzio\Container\SimpleApplicationFactory;
use Mezzio\Router\Route;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

/** @covers \Mezzio\Container\SimpleApplicationFactory */
final class SimpleApplicationFactoryTest extends TestCase
{
    public function testCreatesApplication() : void
    {
        $container = $this->prophesize(ContainerInterface::class);
        $router = $this->prophesize(RouterInterface::class);

        $app = SimpleApplicationFactory::create($container->reveal(), $router->reveal());

        // Check we can perform something simple like adding a route
        $router->addRoute(Argument::type(Route::class))->shouldBeCalled();
        $app->get('/hello-world', new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                return new TextResponse('hey');
            }
        });
    }
}
