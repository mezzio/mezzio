<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\NotFoundHandlerFactory;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class NotFoundHandlerFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    protected function setUp(): void
    {
        $this->response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->get(ResponseInterface::class)->willReturn(function () {
            return $this->response;
        });
    }

    public function testFactoryCreatesInstanceWithoutRendererIfRendererServiceIsMissing()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container->reveal());
        $this->assertInstanceOf(NotFoundHandler::class, $handler);
        $this->assertAttributeInternalType('callable', 'responseFactory', $handler);
        $this->assertAttributeEmpty('renderer', $handler);
    }

    public function testFactoryCreatesInstanceUsingRendererServiceWhenPresent()
    {
        $renderer = $this->prophesize(TemplateRendererInterface::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($renderer);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container->reveal());
        $this->assertAttributeSame($renderer, 'renderer', $handler);
    }

    public function testFactoryUsesConfigured404TemplateWhenPresent()
    {
        $config = [
            'mezzio' => [
                'error_handler' => [
                    'layout' => 'layout::error',
                    'template_404' => 'foo::bar',
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container->reveal());
        $this->assertAttributeEquals(
            $config['mezzio']['error_handler']['layout'],
            'layout',
            $handler
        );
        $this->assertAttributeEquals(
            $config['mezzio']['error_handler']['template_404'],
            'template',
            $handler
        );
    }

    public function testNullifyLayout()
    {
        $config = [
            'mezzio' => [
                'error_handler' => [
                    'template_404' => 'foo::bar',
                    'layout' => null,
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container->reveal());
        // ideally we would like to keep null there,
        // but right now NotFoundHandlerFactory does not accept null for layout
        $this->assertAttributeSame('', 'layout', $handler);
        $this->assertAttributeEquals('foo::bar', 'template', $handler);
    }
}
