<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\MiddlewareContainerFactory;
use Mezzio\MiddlewareContainer;
use MezzioTest\AttributeAssertionTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Psr\Container\ContainerInterface;

class MiddlewareContainerFactoryTest extends TestCase
{
    use ProphecyTrait, AttributeAssertionTrait;

    public function testFactoryCreatesMiddlewareContainerUsingProvidedContainer()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new MiddlewareContainerFactory();

        $middlewareContainer = $factory($container);

        $this->assertInstanceOf(MiddlewareContainer::class, $middlewareContainer);
        $this->assertAttributeSame($container, 'container', $middlewareContainer);
    }
}
