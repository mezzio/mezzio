<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\MiddlewareContainerFactory;
use Mezzio\MiddlewareContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class MiddlewareContainerFactoryTest extends TestCase
{
    public function testFactoryCreatesMiddlewareContainerUsingProvidedContainer() : void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new MiddlewareContainerFactory();

        $middlewareContainer = $factory($container);

        self::assertEquals(new MiddlewareContainer($container), $middlewareContainer);
    }
}
