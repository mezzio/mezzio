<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use ArrayAccess;
use Generator;
use Mezzio\Container\ServerRequestErrorResponseGeneratorFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ServerRequestErrorResponseGeneratorFactoryTest extends TestCase
{
    /**
     * @var ServerRequestErrorResponseGeneratorFactory
     */
    private $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new ServerRequestErrorResponseGeneratorFactory();
    }

    /**
     * @psalm-return Generator<non-empty-string,array{0:array<string,mixed>}>
     */
    public function configurationsWithOverriddenResponseInterfaceFactory(): Generator
    {
        yield 'default' => [
            [
                'dependencies' => [
                    'factories' => [
                        ResponseInterface::class => function (): ResponseInterface {
                            return $this->createMock(ResponseInterface::class);
                        },
                    ],
                ],
            ],
        ];

        yield 'aliased' => [
            [
                'dependencies' => [
                    'aliases' => [
                        ResponseInterface::class => 'CustomResponseInterface'
                    ],
                ],
            ],
        ];

        yield 'delegated' => [
            [
                'dependencies' => [
                    'delegators' => [
                        ResponseInterface::class => [
                            function (): ResponseInterface {
                                return $this->createMock(ResponseInterface::class);
                            }
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testFactoryOnlyRequiresResponseService() : void
    {
        $container = new InMemoryContainer();

        $this->expectException(RuntimeException::class);
        ($this->factory)($container);
    }

    public function testFactoryCreatesGeneratorWhenOnlyResponseServiceIsPresent() : void
    {
        $container = new InMemoryContainer();

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $container->set(ResponseFactoryInterface::class, $responseFactory);

        $generator = ($this->factory)($container);

        self::assertEquals(new ServerRequestErrorResponseGenerator($responseFactory), $generator);
    }

    public function testFactoryCreatesGeneratorUsingConfiguredServices() : void
    {
        $config = [
            'debug' => true,
            'mezzio' => [
                'error_handler' => [
                    'template_error' => 'some::template',
                ],
            ],
        ];
        $renderer = $this->createMock(TemplateRendererInterface::class);

        $container = new InMemoryContainer();
        $container->set('config', $config);
        $container->set(TemplateRendererInterface::class, $renderer);

        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $container->set(ResponseFactoryInterface::class, $responseFactory);

        $generator = ($this->factory)($container);

        self::assertEquals(
            new ServerRequestErrorResponseGenerator(
                $responseFactory,
                true,
                $renderer,
                $config['mezzio']['error_handler']['template_error']
            ),
            $generator
        );
    }

    public function testCanHandleConfigWithArrayAccess(): void
    {
        $config = $this->createMock(ArrayAccess::class);
        $container = new InMemoryContainer();
        $container->set('config', $config);
        $container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));

        ($this->factory)($container);
        $this->expectNotToPerformAssertions();
    }
}
