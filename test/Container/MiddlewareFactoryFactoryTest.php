<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\MiddlewareFactoryFactory;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use MezzioTest\InMemoryContainerTrait;
use PHPUnit\Framework\TestCase;

class MiddlewareFactoryFactoryTest extends TestCase
{
    use InMemoryContainerTrait;

    public function testFactoryProducesMiddlewareFactoryComposingMiddlewareContainerInstance(): void
    {
        $middlewareContainer = $this->createMock(MiddlewareContainer::class);

        $container = $this->createContainer();
        $container->set(MiddlewareContainer::class, $middlewareContainer);

        $factory = new MiddlewareFactoryFactory();

        $middlewareFactory = $factory($container);

        self::assertEquals(new MiddlewareFactory($middlewareContainer), $middlewareFactory);
    }
}
