<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Container;

use Mezzio\Container\Exception\InvalidServiceException;
use Mezzio\Container\WhoopsPageHandlerFactory;
use PHPUnit_Framework_TestCase as TestCase;
use ReflectionFunction;
use ReflectionProperty;
use Whoops\Handler\PrettyPageHandler;

class WhoopsPageHandlerFactoryTest extends TestCase
{
    public function setUp()
    {
        $this->container = $this->prophesize('Interop\Container\ContainerInterface');
        $this->factory   = new WhoopsPageHandlerFactory();
    }

    public function testReturnsAPrettyPageHandler()
    {
        $this->container->has('config')->willReturn(false);
        $factory = $this->factory;

        $result = $factory($this->container->reveal());
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
    }

    public function testWillInjectStringEditor()
    {
        $config = ['whoops' => ['editor' => 'emacs']];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has('emacs')->willReturn(false);

        $factory = $this->factory;
        $result = $factory($this->container->reveal());
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
        $this->assertAttributeEquals($config['whoops']['editor'], 'editor', $result);
    }

    public function testWillInjectCallableEditor()
    {
        $config = ['whoops' => ['editor' => function () {
        }]];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
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
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has('custom')->willReturn(true);
        $this->container->get('custom')->willReturn($editor);

        $factory = $this->factory;
        $result = $factory($this->container->reveal());
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
     */
    public function testInvalidEditorWillRaiseException($editor)
    {
        $config = ['whoops' => ['editor' => $editor]];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);

        $factory = $this->factory;

        $this->setExpectedException(InvalidServiceException::class);
        $factory($this->container->reveal());
    }
}
