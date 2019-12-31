<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Container;

use Mezzio\Container\TemplatedErrorHandlerFactory;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\TemplatedErrorHandler;
use MezzioTest\ContainerTrait;
use PHPUnit_Framework_TestCase as TestCase;
use Prophecy\Prophecy\ObjectProphecy;

/**
 * @covers Mezzio\Container\TemplatedErrorHandlerFactory
 */
class TemplatedErrorHandlerFactoryTest extends TestCase
{
    use ContainerTrait;

    /** @var ObjectProphecy */
    protected $container;

    public function setUp()
    {
        $this->container = $this->mockContainerInterface();
        $this->factory   = new TemplatedErrorHandlerFactory();
    }

    public function testReturnsATemplatedErrorHandler()
    {
        $factory = $this->factory;
        $result  = $factory($this->container->reveal());
        $this->assertInstanceOf(TemplatedErrorHandler::class, $result);
    }

    public function testWillInjectTemplateIntoErrorHandlerWhenServiceIsPresent()
    {
        $renderer = $this->prophesize(TemplateRendererInterface::class);
        $this->injectServiceInContainer($this->container, TemplateRendererInterface::class, $renderer->reveal());

        $factory = $this->factory;
        $result  = $factory($this->container->reveal());
        $this->assertInstanceOf(TemplatedErrorHandler::class, $result);
        $this->assertAttributeInstanceOf(TemplateRendererInterface::class, 'renderer', $result);
    }

    public function testWillInjectTemplateNamesFromConfigurationWhenPresent()
    {
        $config = ['mezzio' => ['error_handler' => [
            'template_404'   => 'error::404',
            'template_error' => 'error::500',
        ]]];
        $this->injectServiceInContainer($this->container, 'config', $config);

        $factory = $this->factory;
        $result  = $factory($this->container->reveal());
        $this->assertInstanceOf(TemplatedErrorHandler::class, $result);
        $this->assertAttributeEquals('error::404', 'template404', $result);
        $this->assertAttributeEquals('error::500', 'templateError', $result);
    }
}
