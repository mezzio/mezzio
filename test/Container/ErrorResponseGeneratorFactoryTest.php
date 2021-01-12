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
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;

class ErrorResponseGeneratorFactoryTest extends TestCase
{
    /** @var InMemoryContainer */
    private $container;

    /** @var TemplateRendererInterface|ObjectProphecy */
    private $renderer;

    public function setUp() : void
    {
        $this->container = new InMemoryContainer();
        $this->renderer  = $this->prophesize(TemplateRendererInterface::class);
    }

    public function testNoConfigurationCreatesInstanceWithDefaults() : void
    {
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container);

        $this->assertInstanceOf(ErrorResponseGenerator::class, $generator);
        $this->assertAttributeEquals(false, 'debug', $generator);
        $this->assertAttributeEmpty('renderer', $generator);
        $this->assertAttributeEquals('error::error', 'template', $generator);
        $this->assertAttributeEquals('layout::default', 'layout', $generator);
    }

    public function testUsesDebugConfigurationToSetDebugFlag() : void
    {
        $this->container->set('config', ['debug' => true]);
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container);

        $this->assertAttributeEquals(true, 'debug', $generator);
        $this->assertAttributeEmpty('renderer', $generator);
        $this->assertAttributeEquals('error::error', 'template', $generator);
        $this->assertAttributeEquals('layout::default', 'layout', $generator);
    }

    public function testUsesConfiguredTemplateRenderToSetGeneratorRenderer() : void
    {
        $this->container->set(TemplateRendererInterface::class, $this->renderer->reveal());
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container);

        $this->assertAttributeEquals(false, 'debug', $generator);
        $this->assertAttributeSame($this->renderer->reveal(), 'renderer', $generator);
        $this->assertAttributeEquals('error::error', 'template', $generator);
        $this->assertAttributeEquals('layout::default', 'layout', $generator);
    }

    public function testUsesTemplateConfigurationToSetTemplate() : void
    {
        $this->container->set('config', [
            'mezzio' => [
                'error_handler' => [
                    'template_error' => 'error::custom',
                    'layout' => 'layout::custom',
                ],
            ],
        ]);
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container);

        $this->assertAttributeEquals(false, 'debug', $generator);
        $this->assertAttributeEmpty('renderer', $generator);
        $this->assertAttributeEquals('error::custom', 'template', $generator);
        $this->assertAttributeEquals('layout::custom', 'layout', $generator);
    }

    public function testNullifyLayout() : void
    {
        $this->container->set('config', [
            'mezzio' => [
                'error_handler' => [
                    'template_error' => 'error::custom',
                    'layout' => null,
                ],
            ],
        ]);
        $factory = new ErrorResponseGeneratorFactory();

        $generator = $factory($this->container);

        $this->assertAttributeEquals(false, 'debug', $generator);
        $this->assertAttributeEmpty('renderer', $generator);
        $this->assertAttributeEquals('error::custom', 'template', $generator);
        // ideally we would like to keep null there,
        // but right now ErrorResponseGeneratorFactory does not accept null for layout
        $this->assertAttributeSame('', 'layout', $generator);
    }
}
