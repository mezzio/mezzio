<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\Container\RequestHandlerRunnerFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use MezzioTest\InMemoryContainer;
use MezzioTest\MutableMemoryContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionProperty;
use RuntimeException;
use Throwable;

final class RequestHandlerRunnerFactoryTest extends TestCase
{
    public function testFactoryProducesRunnerUsingServicesFromContainer(): void
    {
        $container            = new InMemoryContainer();
        $handler              = $this->registerHandlerInContainer($container);
        $emitter              = $this->registerEmitterInContainer($container);
        $serverRequestFactory = $this->registerServerRequestFactoryInContainer($container);
        /** @psalm-suppress NoValue */
        $errorGenerator = $this->registerServerRequestErrorResponseGeneratorInContainer($container);
        self::assertIsCallable($errorGenerator);

        $factory = new RequestHandlerRunnerFactory();

        $runner = $factory($container);

        /** @psalm-suppress MixedArgumentTypeCoercion */
        self::assertEquals(
            new RequestHandlerRunner($handler, $emitter, $serverRequestFactory, $errorGenerator),
            $runner
        );

        $r = new ReflectionProperty($runner, 'serverRequestFactory');
        /** @var callable():ServerRequestInterface $toTest */
        $toTest = $r->getValue($runner);
        $this->assertSame($serverRequestFactory(), $toTest());

        $r = new ReflectionProperty($runner, 'serverRequestErrorResponseGenerator');
        /** @var callable(Throwable):ResponseInterface $toTest */
        $toTest = $r->getValue($runner);
        $e      = new RuntimeException();
        $this->assertSame($errorGenerator($e), $toTest($e));
    }

    public function registerHandlerInContainer(MutableMemoryContainerInterface $container): RequestHandlerInterface
    {
        $app = $this->createMock(RequestHandlerInterface::class);
        $container->set('Mezzio\\ApplicationPipeline', $app);

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
        $factory = static fn(): ServerRequestInterface => $request;
        $container->set(ServerRequestInterface::class, $factory);

        return $factory;
    }

    /**
     * @psalm-suppress InvalidReturnType
     * @psalm-suppress InvalidReturnStatement
     */
    public function registerServerRequestErrorResponseGeneratorInContainer(
        MutableMemoryContainerInterface $container,
    ): MockObject&ServerRequestErrorResponseGenerator {
        $response  = $this->createMock(ResponseInterface::class);
        $generator = $this->createMock(ServerRequestErrorResponseGenerator::class);
        $generator->method('__invoke')
            ->with(self::isInstanceOf(Throwable::class))
            ->willReturn($response);

        $container->set(ServerRequestErrorResponseGenerator::class, $generator);

        return $generator;
    }
}
