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
use Mezzio\ServerRequestErrorResponseGenerator;
use Mezzio\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RequestHandlerRunnerFactoryTest extends TestCase
{
    public function testFactoryProducesRunnerUsingServicesFromContainer()
    {
        $container = $this->prophesize(ContainerInterface::class);
        $handler = $this->registerHandlerInContainer($container);
        $emitter = $this->registerEmitterInContainer($container);
        $serverRequestFactory = $this->registerServerRequestFactoryInContainer($container);
        $errorGenerator = $this->registerServerRequestErroResponseGeneratorInContainer($container);

        $factory = new RequestHandlerRunnerFactory();

        $runner = $factory($container->reveal());

        $this->assertInstanceOf(RequestHandlerRunner::class, $runner);
        $this->assertAttributeSame($handler, 'handler', $runner);
        $this->assertAttributeSame($emitter, 'emitter', $runner);
        $this->assertAttributeSame($serverRequestFactory, 'serverRequestFactory', $runner);
        $this->assertAttributeSame($errorGenerator, 'serverRequestErrorResponseGenerator', $runner);
    }

    public function registerHandlerInContainer($container) : RequestHandlerInterface
    {
        $app = $this->prophesize(RequestHandlerInterface::class)->reveal();
        $container->get(ApplicationPipeline::class)->willReturn($app);
        return $app;
    }

    public function registerEmitterInContainer($container) : EmitterInterface
    {
        $emitter = $this->prophesize(EmitterInterface::class)->reveal();
        $container->get(EmitterInterface::class)->willReturn($emitter);
        return $emitter;
    }

    public function registerServerRequestFactoryInContainer($container) : callable
    {
        $factory = function () {
        };
        $container->get(ServerRequestFactory::class)->willReturn($factory);
        return $factory;
    }

    public function registerServerRequestErroResponseGeneratorInContainer($container) : callable
    {
        $generator = function ($e) {
        };
        $container->get(ServerRequestErrorResponseGenerator::class)->willReturn($generator);
        return $generator;
    }
}
