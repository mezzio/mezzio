<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\MiddlewarePipeInterface;
use Mezzio\Application;
use Mezzio\ApplicationPipeline;
use Mezzio\Container\ApplicationFactory;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\RouteCollector;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ApplicationFactoryTest extends TestCase
{
    public function testFactoryProducesAnApplication() : void
    {
        $middlewareFactory = $this->prophesize(MiddlewareFactory::class)->reveal();
        $pipeline = $this->prophesize(MiddlewarePipeInterface::class)->reveal();
        $routeCollector = $this->prophesize(RouteCollector::class)->reveal();
        $runner = $this->prophesize(RequestHandlerRunner::class)->reveal();

        $container = new InMemoryContainer();
        $container->set(MiddlewareFactory::class, $middlewareFactory);
        $container->set(ApplicationPipeline::class, $pipeline);
        $container->set(RouteCollector::class, $routeCollector);
        $container->set(RequestHandlerRunner::class, $runner);

        $factory = new ApplicationFactory();

        $application = $factory($container);

        $this->assertInstanceOf(Application::class, $application);
        $this->assertAttributeSame($middlewareFactory, 'factory', $application);
        $this->assertAttributeSame($pipeline, 'pipeline', $application);
        $this->assertAttributeSame($routeCollector, 'routes', $application);
        $this->assertAttributeSame($runner, 'runner', $application);
    }
}
