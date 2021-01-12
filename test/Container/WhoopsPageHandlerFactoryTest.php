<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\Exception\InvalidServiceException;
use Mezzio\Container\WhoopsPageHandlerFactory;
use MezzioTest\AttributeAssertionTrait;
use MezzioTest\ContainerTrait;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Whoops\Handler\PrettyPageHandler;

/**
 * @covers Mezzio\Container\WhoopsPageHandlerFactory
 */
class WhoopsPageHandlerFactoryTest extends TestCase
{
    use ContainerTrait, ProphecyTrait, AttributeAssertionTrait;

    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var WhoopsPageHandlerFactory */
    private $factory;

    public function setUp(): void
    {
        $this->container = $this->mockContainerInterface();
        $this->factory   = new WhoopsPageHandlerFactory();
    }

    public function testReturnsAPrettyPageHandler()
    {
        $factory = $this->factory;

        $result = $factory($this->container->reveal());
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
    }

    public function testWillInjectStringEditor()
    {
        $config = ['whoops' => ['editor' => 'emacs']];
        $this->injectServiceInContainer($this->container, 'config', $config);

        $factory = $this->factory;
        $result  = $factory($this->container->reveal());
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
        $this->assertAttributeEquals($config['whoops']['editor'], 'editor', $result);
    }

    public function testWillInjectCallableEditor()
    {
        $config = [
            'whoops' => [
                'editor' => function () {
                },
            ],
        ];
        $this->injectServiceInContainer($this->container, 'config', $config);
        $factory = $this->factory;

        $result = $factory($this->container->reveal());
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
        $this->assertAttributeSame($config['whoops']['editor'], 'editor', $result);
    }

    public function testWillInjectEditorAsAService()
    {
        $config = ['whoops' => ['editor' => 'custom']];
        $editor = function () {
        };
        $this->injectServiceInContainer($this->container, 'config', $config);
        $this->injectServiceInContainer($this->container, 'custom', $editor);

        $factory = $this->factory;
        $result  = $factory($this->container->reveal());
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
        $this->assertAttributeSame($editor, 'editor', $result);
    }

    public function invalidEditors()
    {
        return [
            'true'       => [true],
            'false'      => [false],
            'zero'       => [0],
            'int'        => [1],
            'zero-float' => [0.0],
            'float'      => [1.1],
            'array'      => [['emacs']],
            'object'     => [(object) ['editor' => 'emacs']],
        ];
    }

    /**
     * @dataProvider invalidEditors
     *
     * @param mixed $editor
     */
    public function testInvalidEditorWillRaiseException($editor)
    {
        $config = ['whoops' => ['editor' => $editor]];
        $this->injectServiceInContainer($this->container, 'config', $config);

        $factory = $this->factory;

        $this->expectException(InvalidServiceException::class);
        $factory($this->container->reveal());
    }
}
