<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Closure;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Laminas\Stratigility\Middleware\ErrorResponseGenerator as StratigilityGenerator;
use Mezzio\Container\ErrorHandlerFactory;
use Mezzio\Middleware\ErrorResponseGenerator;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use TypeError;

class ErrorHandlerFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    public function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryFailsIfResponseServiceIsMissing()
    {
        $exception = new RuntimeException();
        $this->container->has(ErrorResponseGenerator::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)->willReturn(false);
        $this->container->get(ErrorResponseGenerator::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)->shouldNotBeCalled();
        $this->container->get(ResponseInterface::class)->willThrow($exception);

        $factory = new ErrorHandlerFactory();

        $this->expectException(RuntimeException::class);
        $factory($this->container->reveal());
    }

    public function testFactoryFailsIfResponseServiceReturnsResponse()
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->container->has(ErrorResponseGenerator::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)->willReturn(false);
        $this->container->get(ErrorResponseGenerator::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)->shouldNotBeCalled();
        $this->container->get(ResponseInterface::class)->willReturn($response);

        $factory = new ErrorHandlerFactory();

        $this->expectException(TypeError::class);
        $factory($this->container->reveal());
    }

    public function testFactoryCreatesHandlerWithStratigilityGeneratorIfNoGeneratorServiceAvailable()
    {
        $this->container->has(ErrorResponseGenerator::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)->willReturn(false);
        $this->container->get(ErrorResponseGenerator::class)->shouldNotBeCalled();
        $this->container->get(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)->shouldNotBeCalled();

        $this->container->get(ResponseInterface::class)->willReturn(function () {
        });

        $factory = new ErrorHandlerFactory();
        $handler = $factory($this->container->reveal());

        $this->assertInstanceOf(ErrorHandler::class, $handler);
        $this->assertAttributeInstanceOf(Closure::class, 'responseFactory', $handler);
        $this->assertAttributeInstanceOf(StratigilityGenerator::class, 'responseGenerator', $handler);
    }

    public function testFactoryCreatesHandlerWithGeneratorIfGeneratorServiceAvailable()
    {
        $generator = $this->prophesize(ErrorResponseGenerator::class)->reveal();
        $this->container->has(ErrorResponseGenerator::class)->willReturn(true);
        $this->container->get(ErrorResponseGenerator::class)->willReturn($generator);

        $this->container->get(ResponseInterface::class)->willReturn(function () {
        });

        $factory = new ErrorHandlerFactory();
        $handler = $factory($this->container->reveal());

        $this->assertInstanceOf(ErrorHandler::class, $handler);
        $this->assertAttributeInstanceOf(Closure::class, 'responseFactory', $handler);
        $this->assertAttributeSame($generator, 'responseGenerator', $handler);
    }
}
