<?php

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
    private InMemoryContainer $container;

    private WhoopsPageHandlerFactory $factory;

    public function setUp(): void
    {
        $this->container = new InMemoryContainer();
        $this->factory   = new WhoopsPageHandlerFactory();
    }

    public function testReturnsAPrettyPageHandler(): void
    {
        $factory = $this->factory;

        $result = $factory($this->container);

        self::assertEquals(new PrettyPageHandler(), $result);
        $this->assertInstanceOf(PrettyPageHandler::class, $result);
    }

    public function testWillInjectStringEditor(): void
    {
        $config = ['whoops' => ['editor' => 'emacs']];
        $this->container->set('config', $config);

        $factory = $this->factory;
        $result  = $factory($this->container);

        $expected = new PrettyPageHandler();
        $expected->setEditor($config['whoops']['editor']);

        self::assertEquals($expected, $result);
    }

    public function testWillInjectCallableEditor(): void
    {
        $config = [
            'whoops' => [
                'editor' => static function (): void {
                },
            ],
        ];
        $this->container->set('config', $config);
        $factory = $this->factory;

        $result = $factory($this->container);

        $expected = new PrettyPageHandler();
        $expected->setEditor($config['whoops']['editor']);

        self::assertEquals($expected, $result);
    }

    public function testWillInjectEditorAsAService(): void
    {
        $config = ['whoops' => ['editor' => 'custom']];
        $editor = static function (): void {
        };
        $this->container->set('config', $config);
        $this->container->set('custom', $editor);

        $factory = $this->factory;
        $result  = $factory($this->container);

        $expected = new PrettyPageHandler();
        $expected->setEditor($editor);

        self::assertEquals($expected, $result);
    }

    public function invalidEditors(): array
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
    public function testInvalidEditorWillRaiseException(mixed $editor): void
    {
        $config = ['whoops' => ['editor' => $editor]];
        $this->container->set('config', $config);

        $factory = $this->factory;

        $this->expectException(InvalidServiceException::class);
        $factory($this->container);
    }
}
