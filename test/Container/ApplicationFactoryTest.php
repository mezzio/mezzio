<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Mezzio\Application;
use Mezzio\ApplicationPipeline;
use Mezzio\Container\ApplicationFactory;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\RouteCollector;
use MezzioTest\InMemoryContainerTrait;
use PHPUnit\Framework\TestCase;

class ApplicationFactoryTest extends TestCase
{
    use InMemoryContainerTrait;

    public function testFactoryProducesAnApplication(): void
    {
        $middlewareFactory = $this->createMock(MiddlewareFactory::class);
        $pipeline          = $this->createMock(MiddlewarePipeInterface::class);
        $routeCollector    = $this->createMock(RouteCollector::class);
        $runner            = $this->createMock(RequestHandlerRunnerInterface::class);

        $container = $this->createContainer();
        $container->set(MiddlewareFactory::class, $middlewareFactory);
        $container->set(ApplicationPipeline::class, $pipeline);
        $container->set(RouteCollector::class, $routeCollector);
        $container->set(RequestHandlerRunnerInterface::class, $runner);

        $factory = new ApplicationFactory();

        $application = $factory($container);

        self::assertEquals(new Application($middlewareFactory, $pipeline, $routeCollector, $runner), $application);
    }
}
