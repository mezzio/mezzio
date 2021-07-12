<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use ArrayAccess;
use Generator;
use Mezzio\Container\NotFoundHandlerFactory;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Template\TemplateRendererInterface;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class NotFoundHandlerFactoryTest extends TestCase
{
    /** @var InMemoryContainer */
    private $container;

    /** @var ResponseInterface&MockObject */
    private $response;

    /**
     * @var NotFoundHandlerFactory
     */
    private $factory;

    protected function setUp(): void
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->container = new InMemoryContainer();
        $this->container->set(ResponseFactoryInterface::class, $this->createMock(ResponseFactoryInterface::class));
        $this->factory = new NotFoundHandlerFactory();
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

    public function testFactoryCreatesInstanceWithoutRendererIfRendererServiceIsMissing() : void
    {
        $handler = ($this->factory)($this->container);

        self::assertEquals(new NotFoundHandler($this->container->get(ResponseFactoryInterface::class)), $handler);
    }

    public function testFactoryCreatesInstanceUsingRendererServiceWhenPresent() : void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $this->container->set(TemplateRendererInterface::class, $renderer);

        $handler = ($this->factory)($this->container);

        self::assertEquals(
            new NotFoundHandler(
                $this->container->get(ResponseFactoryInterface::class),
                $renderer
            ),
            $handler
        );
    }

    public function testFactoryUsesConfigured404TemplateWhenPresent() : void
    {
        $config = [
            'mezzio' => [
                'error_handler' => [
                    'layout' => 'layout::error',
                    'template_404' => 'foo::bar',
                ],
            ],
        ];
        $this->container->set('config', $config);

        $handler = ($this->factory)($this->container);

        self::assertEquals(
            new NotFoundHandler(
                $this->container->get(ResponseFactoryInterface::class),
                null,
                $config['mezzio']['error_handler']['template_404'],
                $config['mezzio']['error_handler']['layout']
            ),
            $handler
        );
    }

    public function testNullifyLayout() : void
    {
        $config = [
            'mezzio' => [
                'error_handler' => [
                    'template_404' => 'foo::bar',
                    'layout' => null,
                ],
            ],
        ];
        $this->container->set('config', $config);

        $handler = ($this->factory)($this->container);

        // ideally we would like to keep null there,
        // but right now NotFoundHandlerFactory does not accept null for layout
        self::assertEquals(
            new NotFoundHandler(
                $this->container->get(ResponseFactoryInterface::class),
                null,
                $config['mezzio']['error_handler']['template_404'],
                ''
            ),
            $handler
        );
    }

    public function testCanHandleConfigWithArrayAccess(): void
    {
        $config = $this->createMock(ArrayAccess::class);
        $this->container->set('config', $config);

        ($this->factory)($this->container);
        $this->expectNotToPerformAssertions();
    }
}
