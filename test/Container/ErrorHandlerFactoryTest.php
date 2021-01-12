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
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use TypeError;

class ErrorHandlerFactoryTest extends TestCase
{
    /** @var InMemoryContainer */
    private $container;

    public function setUp() : void
    {
        $this->container = new InMemoryContainer();
    }

    public function testFactoryFailsIfResponseServiceIsMissing() : void
    {
        $factory = new ErrorHandlerFactory();

        $this->expectException(RuntimeException::class);
        $factory($this->container);
    }

    public function testFactoryFailsIfResponseServiceReturnsResponse() : void
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->container->set(ResponseInterface::class, $response);

        $factory = new ErrorHandlerFactory();

        $this->expectException(TypeError::class);
        $factory($this->container);
    }

    public function testFactoryCreatesHandlerWithStratigilityGeneratorIfNoGeneratorServiceAvailable() : void
    {
        $this->container->set(ResponseInterface::class, function () {
        });

        $factory = new ErrorHandlerFactory();
        $handler = $factory($this->container);

        $this->assertInstanceOf(ErrorHandler::class, $handler);
        $this->assertAttributeInstanceOf(Closure::class, 'responseFactory', $handler);
        $this->assertAttributeInstanceOf(StratigilityGenerator::class, 'responseGenerator', $handler);
    }

    public function testFactoryCreatesHandlerWithGeneratorIfGeneratorServiceAvailable() : void
    {
        $generator = $this->prophesize(ErrorResponseGenerator::class)->reveal();
        $this->container->set(ErrorResponseGenerator::class, $generator);

        $this->container->set(ResponseInterface::class, function () {
        });

        $factory = new ErrorHandlerFactory();
        $handler = $factory($this->container);

        $this->assertInstanceOf(ErrorHandler::class, $handler);
        $this->assertAttributeInstanceOf(Closure::class, 'responseFactory', $handler);
        $this->assertAttributeSame($generator, 'responseGenerator', $handler);
    }
}
