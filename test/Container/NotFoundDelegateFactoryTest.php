<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Container;

use Laminas\Diactoros\Response;
use Mezzio\Container\NotFoundDelegateFactory;
use Mezzio\Delegate\NotFoundDelegate;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class NotFoundDelegateFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    protected function setUp()
    {
        $this->container = $this->prophesize(ContainerInterface::class);
    }

    public function testFactoryCreatesInstanceWithoutRendererIfRendererServiceIsMissing()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new NotFoundDelegateFactory();

        $delegate = $factory($this->container->reveal());
        $this->assertInstanceOf(NotFoundDelegate::class, $delegate);
        $this->assertAttributeInstanceOf(Response::class, 'responsePrototype', $delegate);
        $this->assertAttributeEmpty('renderer', $delegate);
    }

    public function testFactoryCreatesInstanceUsingRendererServiceWhenPresent()
    {
        $renderer = $this->prophesize(TemplateRendererInterface::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($renderer);
        $factory = new NotFoundDelegateFactory();

        $delegate = $factory($this->container->reveal());
        $this->assertAttributeSame($renderer, 'renderer', $delegate);
    }

    public function testFactoryUsesConfigured404TemplateWhenPresent()
    {
        $config = [
            'mezzio' => [
                'error_handler' => [
                    'template_404' => 'foo::bar',
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new NotFoundDelegateFactory();

        $delegate = $factory($this->container->reveal());
        $this->assertAttributeEquals(
            $config['mezzio']['error_handler']['template_404'],
            'template',
            $delegate
        );
    }
}
