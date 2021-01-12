<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Closure;
use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Container\ServerRequestFactoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ServerRequestFactoryFactoryTest extends TestCase
{
    public function testFactoryReturnsCallable() : callable
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new ServerRequestFactoryFactory();

        $generatedFactory = $factory($container);

        $this->assertInternalType('callable', $generatedFactory);

        return $generatedFactory;
    }

    /**
     * Some containers do not allow returning generic PHP callables, and will
     * error when one is returned; one example is Auryn. As such, the factory
     * cannot simply return a callable referencing the
     * ServerRequestFactory::fromGlobals method, but must be decorated as a
     * closure.
     *
     * @depends testFactoryReturnsCallable
     */
    public function testFactoryIsAClosure(callable $factory) : void
    {
        $this->assertNotSame([ServerRequestFactory::class, 'fromGlobals'], $factory);
        $this->assertNotSame(ServerRequestFactory::class . '::fromGlobals', $factory);
        $this->assertInstanceOf(Closure::class, $factory);
    }
}
