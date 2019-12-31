<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Container;

use Mezzio\Container\WhoopsErrorHandlerFactory;
use Mezzio\Template\TemplateInterface;
use Mezzio\WhoopsErrorHandler;
use PHPUnit_Framework_TestCase as TestCase;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

class WhoopsErrorHandlerFactoryTest extends TestCase
{
    public function setUp()
    {
        $whoops      = $this->prophesize(Whoops::class);
        $pageHandler = $this->prophesize(PrettyPageHandler::class);
        $this->container = $this->prophesize('Interop\Container\ContainerInterface');
        $this->container->get('Mezzio\WhoopsPageHandler')->willReturn($pageHandler->reveal());
        $this->container->get('Mezzio\Whoops')->willReturn($whoops->reveal());

        $this->factory   = new WhoopsErrorHandlerFactory();
    }

    public function testReturnsAWhoopsErrorHandler()
    {
        $this->container->has(TemplateInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateInterface::class)->willReturn(false);
        $this->container->has('config')->willReturn(false);

        $factory = $this->factory;
        $result  = $factory($this->container->reveal());
        $this->assertInstanceOf(WhoopsErrorHandler::class, $result);
    }

    public function testWillInjectTemplateIntoErrorHandlerWhenServiceIsPresent()
    {
        $template = $this->prophesize(TemplateInterface::class);
        $this->container->has(TemplateInterface::class)->willReturn(true);
        $this->container->get(TemplateInterface::class)->willReturn($template->reveal());
        $this->container->has('config')->willReturn(false);

        $factory = $this->factory;
        $result  = $factory($this->container->reveal());
        $this->assertInstanceOf(WhoopsErrorHandler::class, $result);
        $this->assertAttributeInstanceOf(TemplateInterface::class, 'template', $result);
    }

    public function testWillInjectTemplateNamesFromConfigurationWhenPresent()
    {
        $config = ['mezzio' => ['error_handler' => [
            'template_404'   => 'error::404',
            'template_error' => 'error::500',
        ]]];
        $this->container->has(TemplateInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateInterface::class)->willReturn(false);
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $factory = $this->factory;
        $result  = $factory($this->container->reveal());
        $this->assertInstanceOf(WhoopsErrorHandler::class, $result);
        $this->assertAttributeEquals('error::404', 'template404', $result);
        $this->assertAttributeEquals('error::500', 'templateError', $result);
    }
}
