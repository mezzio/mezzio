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
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\PrettyPageHandler;

/**
 * @covers Mezzio\Container\WhoopsPageHandlerFactory
 */
class WhoopsPageHandlerFactoryTest extends TestCase
{
    /** @var InMemoryContainer */
    private $container;

    /** @var WhoopsPageHandlerFactory */
    private $factory;

    public function setUp() : void
    {
        $this->container = new InMemoryContainer();
        $this->factory   = new WhoopsPageHandlerFactory();
    }

    public function testReturnsAPrettyPageHandler() : void
    {
        $factory = $this->factory;

        $result = $factory($this->container);
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
    }

    public function testWillInjectStringEditor() : void
    {
        $config = ['whoops' => ['editor' => 'emacs']];
        $this->container->set('config', $config);

        $factory = $this->factory;
        $result  = $factory($this->container);
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
        $this->assertAttributeEquals($config['whoops']['editor'], 'editor', $result);
    }

    public function testWillInjectCallableEditor() : void
    {
        $config = [
            'whoops' => [
                'editor' => function () {
                },
            ],
        ];
        $this->container->set('config', $config);
        $factory = $this->factory;

        $result = $factory($this->container);
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
        $this->assertAttributeSame($config['whoops']['editor'], 'editor', $result);
    }

    public function testWillInjectEditorAsAService() : void
    {
        $config = ['whoops' => ['editor' => 'custom']];
        $editor = function () {
        };
        $this->container->set('config', $config);
        $this->container->set('custom', $editor);

        $factory = $this->factory;
        $result  = $factory($this->container);
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
        $this->assertAttributeSame($editor, 'editor', $result);
    }

    public function invalidEditors() : array
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
    public function testInvalidEditorWillRaiseException($editor) : void
    {
        $config = ['whoops' => ['editor' => $editor]];
        $this->container->set('config', $config);

        $factory = $this->factory;

        $this->expectException(InvalidServiceException::class);
        $factory($this->container);
    }
}
