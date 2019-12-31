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
use Mezzio\Router\PathBasedRoutingMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ApplicationFactoryTest extends TestCase
{
    public function testFactoryProducesAnApplication()
    {
        $middlewareFactory = $this->prophesize(MiddlewareFactory::class)->reveal();
        $pipeline = $this->prophesize(MiddlewarePipeInterface::class)->reveal();
        $routeMiddleware = $this->prophesize(PathBasedRoutingMiddleware::class)->reveal();
        $runner = $this->prophesize(RequestHandlerRunner::class)->reveal();

        $container = $this->prophesize(ContainerInterface::class);
        $container->get(MiddlewareFactory::class)->willReturn($middlewareFactory);
        $container->get(ApplicationPipeline::class)->willReturn($pipeline);
        $container->get(PathBasedRoutingMiddleware::class)->willReturn($routeMiddleware);
        $container->get(RequestHandlerRunner::class)->willReturn($runner);

        $factory = new ApplicationFactory();

        $application = $factory($container->reveal());

        $this->assertInstanceOf(Application::class, $application);
        $this->assertAttributeSame($middlewareFactory, 'factory', $application);
        $this->assertAttributeSame($pipeline, 'pipeline', $application);
        $this->assertAttributeSame($routeMiddleware, 'routes', $application);
        $this->assertAttributeSame($runner, 'runner', $application);
    }
}
