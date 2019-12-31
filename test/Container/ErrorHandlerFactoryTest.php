<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Container;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Laminas\Stratigility\Middleware\ErrorResponseGenerator as StratigilityGenerator;
use Mezzio\Container\ErrorHandlerFactory;
use Mezzio\Middleware\ErrorResponseGenerator;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class ErrorHandlerFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryCreatesHandlerWithStratigilityGeneratorIfNoGeneratorServiceAvailable()
    {
        $this->container->has(ErrorResponseGenerator::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)->willReturn(false);

        $factory = new ErrorHandlerFactory();
        $handler = $factory($this->container->reveal());

        $this->assertInstanceOf(ErrorHandler::class, $handler);
        $this->assertAttributeInstanceOf(ResponseInterface::class, 'responsePrototype', $handler);
        $this->assertAttributeInstanceOf(StratigilityGenerator::class, 'responseGenerator', $handler);
    }

    public function testFactoryCreatesHandlerWithGeneratorIfGeneratorServiceAvailable()
    {
        $generator = $this->prophesize(ErrorResponseGenerator::class)->reveal();
        $this->container->has(ErrorResponseGenerator::class)->willReturn(true);
        $this->container->get(ErrorResponseGenerator::class)->willReturn($generator);

        $factory = new ErrorHandlerFactory();
        $handler = $factory($this->container->reveal());

        $this->assertInstanceOf(ErrorHandler::class, $handler);
        $this->assertAttributeInstanceOf(ResponseInterface::class, 'responsePrototype', $handler);
        $this->assertAttributeSame($generator, 'responseGenerator', $handler);
    }
}
