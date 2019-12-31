<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\NotFoundHandlerFactory;
use Mezzio\Delegate\NotFoundDelegate;
use Mezzio\Middleware\NotFoundHandler;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class NotFoundHandlerFactoryTest extends TestCase
{
    public function testUsesComposedNotFoundDelegateServiceToCreateNotFoundHandler()
    {
        $delegate  = $this->prophesize(NotFoundDelegate::class)->reveal();
        $container = $this->prophesize(ContainerInterface::class);
        $container->get(NotFoundDelegate::class)->willReturn($delegate);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($container->reveal());

        $this->assertInstanceOf(NotFoundHandler::class, $handler);
        $this->assertAttributeSame($delegate, 'internalDelegate', $handler);
    }
}
