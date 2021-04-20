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

        self::assertEquals(
            new RequestHandlerRunner($handler, $emitter, $serverRequestFactory, $errorGenerator),
            $runner
        );

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
        $app = $this->createMock(RequestHandlerInterface::class);
        $container->set(ApplicationPipeline::class, $app);

        return $app;
    }

    public function registerEmitterInContainer(InMemoryContainer $container) : EmitterInterface
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $container->set(EmitterInterface::class, $emitter);

        return $emitter;
    }

    public function registerServerRequestFactoryInContainer(InMemoryContainer $container) : callable
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $factory = function () use ($request): ServerRequestInterface {
            return $request;
        };
        $container->set(ServerRequestInterface::class, $factory);

        return $factory;
    }

    /**
     * @psalm-return \PHPUnit\Framework\MockObject\MockObject&ServerRequestErrorResponseGenerator
     */
    public function registerServerRequestErrorResponseGeneratorInContainer(InMemoryContainer $container)
    {
        $response = $this->createMock(ResponseInterface::class);
        $generator = $this->createMock(ServerRequestErrorResponseGenerator::class);
        $generator->method('__invoke')
            ->with(self::isInstanceOf(Throwable::class))
            ->willReturn($response);

        $container->set(ServerRequestErrorResponseGenerator::class, $generator);

        return $generator;
    }
}
