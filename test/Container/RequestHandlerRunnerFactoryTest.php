<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\ApplicationPipeline;
use Mezzio\Container\RequestHandlerRunnerFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
use RuntimeException;
use Throwable;

class RequestHandlerRunnerFactoryTest extends TestCase
{
    public function testFactoryProducesRunnerUsingServicesFromContainer() : void
    {
        $container = new InMemoryContainer();
        $handler = $this->registerHandlerInContainer($container);
        $emitter = $this->registerEmitterInContainer($container);
        $serverRequestFactory = $this->registerServerRequestFactoryInContainer($container);
        $errorGenerator = $this->registerServerRequestErrorResponseGeneratorInContainer($container);

        $factory = new RequestHandlerRunnerFactory();

        $runner = $factory($container);

        $this->assertInstanceOf(RequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($handler, 'handler', $runner);
        $this->assertAttributeSame($emitter, 'emitter', $runner);

        $this->assertAttributeNotSame($serverRequestFactory, 'serverRequestFactory', $runner);
        $this->assertAttributeNotSame($errorGenerator, 'serverRequestErrorResponseGenerator', $runner);

        $r = new ReflectionProperty($runner, 'serverRequestFactory');
        $r->setAccessible(true);
        $toTest = $r->getValue($runner);
        $this->assertSame($serverRequestFactory(), $toTest());

        $r = new ReflectionProperty($runner, 'serverRequestErrorResponseGenerator');
        $r->setAccessible(true);
        $toTest = $r->getValue($runner);
        $e = new RuntimeException();
        $this->assertSame($errorGenerator($e), $toTest($e));
    }

    public function registerHandlerInContainer(InMemoryContainer $container) : RequestHandlerInterface
    {
        $app = $this->prophesize(RequestHandlerInterface::class)->reveal();
        $container->set(ApplicationPipeline::class, $app);

        return $app;
    }

    public function registerEmitterInContainer(InMemoryContainer $container) : EmitterInterface
    {
        $emitter = $this->prophesize(EmitterInterface::class)->reveal();
        $container->set(EmitterInterface::class, $emitter);

        return $emitter;
    }

    public function registerServerRequestFactoryInContainer(InMemoryContainer $container) : callable
    {
        $request = $this->prophesize(ServerRequestInterface::class)->reveal();
        $factory = function () use ($request) {
            return $request;
        };
        $container->set(ServerRequestInterface::class, $factory);

        return $factory;
    }

    public function registerServerRequestErrorResponseGeneratorInContainer(InMemoryContainer $container) : callable
    {
        $response = $this->prophesize(ResponseInterface::class)->reveal();
        $generator = $this->prophesize(ServerRequestErrorResponseGenerator::class);
        $generator->__invoke(Argument::type(Throwable::class))->willReturn($response);

        $container->set(ServerRequestErrorResponseGenerator::class, $generator->reveal());

        return $generator->reveal();
    }
}
