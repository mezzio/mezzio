<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\ErrorResponseGeneratorFactory;
use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class ErrorResponseGeneratorFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var TemplateRendererInterface|ObjectProphecy */
    private $renderer;

    public function setUp(): void
    {
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->renderer  = $this->prophesize(TemplateRendererInterface::class);
    }

    public function testNoConfigurationCreatesInstanceWithDefaults()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container->reveal());

        $this->assertInstanceOf(ErrorResponseGenerator::class, $generator);
        $this->assertAttributeEquals(false, 'debug', $generator);
        $this->assertAttributeEmpty('renderer', $generator);
        $this->assertAttributeEquals('error::error', 'template', $generator);
        $this->assertAttributeEquals('layout::default', 'layout', $generator);
    }

    public function testUsesDebugConfigurationToSetDebugFlag()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn(['debug' => true]);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container->reveal());

        $this->assertAttributeEquals(true, 'debug', $generator);
        $this->assertAttributeEmpty('renderer', $generator);
        $this->assertAttributeEquals('error::error', 'template', $generator);
        $this->assertAttributeEquals('layout::default', 'layout', $generator);
    }

    public function testUsesConfiguredTemplateRenderToSetGeneratorRenderer()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->will([$this->renderer, 'reveal']);
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container->reveal());

        $this->assertAttributeEquals(false, 'debug', $generator);
        $this->assertAttributeSame($this->renderer->reveal(), 'renderer', $generator);
        $this->assertAttributeEquals('error::error', 'template', $generator);
        $this->assertAttributeEquals('layout::default', 'layout', $generator);
    }

    public function testUsesTemplateConfigurationToSetTemplate()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'mezzio' => [
                'error_handler' => [
                    'template_error' => 'error::custom',
                    'layout' => 'layout::custom',
                ],
            ],
        ]);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container->reveal());

        $this->assertAttributeEquals(false, 'debug', $generator);
        $this->assertAttributeEmpty('renderer', $generator);
        $this->assertAttributeEquals('error::custom', 'template', $generator);
        $this->assertAttributeEquals('layout::custom', 'layout', $generator);
    }

    public function testNullifyLayout()
    {
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn([
            'mezzio' => [
                'error_handler' => [
                    'template_error' => 'error::custom',
                    'layout' => null,
                ],
            ],
        ]);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container->reveal());

        $this->assertAttributeEquals(false, 'debug', $generator);
        $this->assertAttributeEmpty('renderer', $generator);
        $this->assertAttributeEquals('error::custom', 'template', $generator);
        // ideally we would like to keep null there,
        // but right now ErrorResponseGeneratorFactory does not accept null for layout
        $this->assertAttributeSame('', 'layout', $generator);
    }
}
