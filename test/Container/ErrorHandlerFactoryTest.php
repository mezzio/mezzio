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
        $this->container->set(ResponseInterface::class, $this->createMock(ResponseInterface::class));

        $factory = new ErrorHandlerFactory();

        $this->expectException(TypeError::class);
        $factory($this->container);
    }

    public function testFactoryCreatesHandlerWithStratigilityGeneratorIfNoGeneratorServiceAvailable() : void
    {
        $responseFactory = function () {
        };
        $this->container->set(ResponseInterface::class, $responseFactory);

        $factory = new ErrorHandlerFactory();
        $handler = $factory($this->container);

        self::assertEquals(new ErrorHandler($responseFactory, new StratigilityGenerator()), $handler);
    }

    public function testFactoryCreatesHandlerWithGeneratorIfGeneratorServiceAvailable() : void
    {
        $generator = $this->createMock(ErrorResponseGenerator::class);
        $responseFactory = function () {
        };

        $this->container->set(ErrorResponseGenerator::class, $generator);
        $this->container->set(ResponseInterface::class, $responseFactory);

        $factory = new ErrorHandlerFactory();
        $handler = $factory($this->container);

        self::assertEquals(new ErrorHandler($responseFactory, $generator), $handler);
    }
}
