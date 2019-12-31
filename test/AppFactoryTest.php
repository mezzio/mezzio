<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest;

use Mezzio\AppFactory;
use Mezzio\Application;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionProperty;

class AppFactoryTest extends TestCase
{
    public function getRouterFromApplication(Application $app)
    {
        $r = new ReflectionProperty($app, 'router');
        $r->setAccessible(true);
        return $r->getValue($app);
    }

    public function testFactoryReturnsApplicationInstance()
    {
        $app = AppFactory::create();
        $this->assertInstanceOf('Mezzio\Application', $app);
    }

    public function testFactoryUsesAuraRouterByDefault()
    {
        $app    = AppFactory::create();
        $router = $this->getRouterFromApplication($app);
        $this->assertInstanceOf('Mezzio\Router\Aura', $router);
    }

    public function testFactoryUsesLaminasServiceManagerByDefault()
    {
        $app        = AppFactory::create();
        $container  = $app->getContainer();
        $this->assertInstanceOf('Laminas\ServiceManager\ServiceManager', $container);
    }

    public function testFactoryUsesEmitterStackWithSapiEmitterComposedByDefault()
    {
        $app     = AppFactory::create();
        $emitter = $app->getEmitter();
        $this->assertInstanceOf('Mezzio\Emitter\EmitterStack', $emitter);

        $this->assertCount(1, $emitter);
        $this->assertInstanceOf('Laminas\Diactoros\Response\SapiEmitter', $emitter->pop());
    }

    public function testFactoryAllowsPassingContainerToUse()
    {
        $container = $this->prophesize('Interop\Container\ContainerInterface');
        $app       = AppFactory::create($container->reveal());
        $test      = $app->getContainer();
        $this->assertSame($container->reveal(), $test);
    }

    public function testFactoryAllowsPassingRouterToUse()
    {
        $router = $this->prophesize('Mezzio\Router\RouterInterface');
        $app    = AppFactory::create(null, $router->reveal());
        $test   = $this->getRouterFromApplication($app);
        $this->assertSame($router->reveal(), $test);
    }
}
