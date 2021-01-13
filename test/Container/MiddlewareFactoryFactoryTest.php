<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\MiddlewareFactoryFactory;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;

class MiddlewareFactoryFactoryTest extends TestCase
{
    public function testFactoryProducesMiddlewareFactoryComposingMiddlewareContainerInstance() : void
    {
        $middlewareContainer = $this->createMock(MiddlewareContainer::class);

        $container = new InMemoryContainer();
        $container->set(MiddlewareContainer::class, $middlewareContainer);

        $factory = new MiddlewareFactoryFactory();

        $middlewareFactory = $factory($container);

        self::assertEquals(new MiddlewareFactory($middlewareContainer), $middlewareFactory);
    }
}
