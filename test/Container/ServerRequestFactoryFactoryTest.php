<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\Diactoros\ServerRequestFactory;
use Mezzio\Container\ServerRequestFactoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ServerRequestFactoryFactoryTest extends TestCase
{
    public function testFactoryReturnsCallable()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new ServerRequestFactoryFactory();

        $generatedFactory = $factory($container);

        $this->assertInternalType('callable', $generatedFactory);

        return $generatedFactory;
    }

    /**
     * @depends testFactoryReturnsCallable
     */
    public function testFactoryUsesDiactorosFromGlobals(callable $factory)
    {
        $this->assertSame([ServerRequestFactory::class, 'fromGlobals'], $factory);
    }
}
