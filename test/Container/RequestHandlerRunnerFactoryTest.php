<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\ApplicationPipeline;
use Mezzio\Container\RequestHandlerRunnerFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use MezzioTest\InMemoryContainerTrait;
use MezzioTest\MutableMemoryContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
use RuntimeException;
use Throwable;

class RequestHandlerRunnerFactoryTest extends TestCase
{
    use InMemoryContainerTrait;

    public function testFactoryProducesRunnerUsingServicesFromContainer(): void
    {
        $container            = $this->createContainer();
        $handler              = $this->registerHandlerInContainer($container);
        $emitter              = $this->registerEmitterInContainer($container);
        $serverRequestFactory = $this->registerServerRequestFactoryInContainer($container);
        $errorGenerator       = $this->registerServerRequestErrorResponseGeneratorInContainer($container);

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
        $e      = new RuntimeException();
        $this->assertSame($errorGenerator($e), $toTest($e));
    }

    public function registerHandlerInContainer(MutableMemoryContainerInterface $container): RequestHandlerInterface
    {
        $app = $this->createMock(RequestHandlerInterface::class);
        $container->set(ApplicationPipeline::class, $app);

        return $app;
    }

    public function registerEmitterInContainer(MutableMemoryContainerInterface $container): EmitterInterface
    {
        $emitter = $this->createMock(EmitterInterface::class);
        $container->set(EmitterInterface::class, $emitter);

        return $emitter;
    }

    /**
     * @return callable():ServerRequestInterface
     */
    public function registerServerRequestFactoryInContainer(MutableMemoryContainerInterface $container): callable
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $factory = function () use ($request): ServerRequestInterface {
            return $request;
        };
        $container->set(ServerRequestInterface::class, $factory);

        return $factory;
    }

    /**
     * @psalm-return MockObject&ServerRequestErrorResponseGenerator
     */
    public function registerServerRequestErrorResponseGeneratorInContainer(MutableMemoryContainerInterface $container)
    {
        $response  = $this->createMock(ResponseInterface::class);
        $generator = $this->createMock(ServerRequestErrorResponseGenerator::class);
        $generator->method('__invoke')
            ->with(self::isInstanceOf(Throwable::class))
            ->willReturn($response);

        $container->set(ServerRequestErrorResponseGenerator::class, $generator);

        return $generator;
    }
}
