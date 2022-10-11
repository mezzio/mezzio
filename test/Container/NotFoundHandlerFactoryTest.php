<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use ArrayAccess;
use Generator;
use Mezzio\Container\NotFoundHandlerFactory;
use Mezzio\Container\ResponseFactoryFactory;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Response\CallableResponseFactoryDecorator;
use Mezzio\Template\TemplateRendererInterface;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

class NotFoundHandlerFactoryTest extends TestCase
{
    private InMemoryContainer $container;

    /** @var ResponseInterface&MockObject */
    private $response;

    private NotFoundHandlerFactory $factory;

    protected function setUp(): void
    {
        $this->response  = $this->createMock(ResponseInterface::class);
        $this->container = new InMemoryContainer();
        $this->container->set(ResponseInterface::class, fn() => $this->response);
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
                        ResponseInterface::class
                            => fn(): ResponseInterface => $this->createMock(ResponseInterface::class),
                    ],
                ],
            ],
        ];

        yield 'aliased' => [
            [
                'dependencies' => [
                    'aliases' => [
                        ResponseInterface::class => 'CustomResponseInterface',
                    ],
                ],
            ],
        ];

        yield 'delegated' => [
            [
                'dependencies' => [
                    'delegators' => [
                        ResponseInterface::class => [
                            fn(): ResponseInterface => $this->createMock(ResponseInterface::class),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testFactoryCreatesInstanceWithoutRendererIfRendererServiceIsMissing(): void
    {
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container);

        self::assertEquals(new NotFoundHandler($this->container->get(ResponseInterface::class)), $handler);
    }

    public function testFactoryCreatesInstanceUsingRendererServiceWhenPresent(): void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $this->container->set(TemplateRendererInterface::class, $renderer);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container);

        self::assertEquals(new NotFoundHandler($this->container->get(ResponseInterface::class), $renderer), $handler);
    }

    public function testFactoryUsesConfigured404TemplateWhenPresent(): void
    {
        $config = [
            'mezzio' => [
                'error_handler' => [
                    'layout'       => 'layout::error',
                    'template_404' => 'foo::bar',
                ],
            ],
        ];
        $this->container->set('config', $config);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container);

        self::assertEquals(
            new NotFoundHandler(
                $this->container->get(ResponseInterface::class),
                null,
                $config['mezzio']['error_handler']['template_404'],
                $config['mezzio']['error_handler']['layout']
            ),
            $handler
        );
    }

    public function testNullifyLayout(): void
    {
        $config = [
            'mezzio' => [
                'error_handler' => [
                    'template_404' => 'foo::bar',
                    'layout'       => null,
                ],
            ],
        ];
        $this->container->set('config', $config);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container);

        // ideally we would like to keep null there,
        // but right now NotFoundHandlerFactory does not accept null for layout
        self::assertEquals(
            new NotFoundHandler(
                $this->container->get(ResponseInterface::class),
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

        $factory = new NotFoundHandlerFactory();
        $factory($this->container);
        $this->expectNotToPerformAssertions();
    }

    public function testWillUseResponseFactoryInterfaceFromContainerWhenApplicationFactoryIsNotOverridden(): void
    {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $container       = new InMemoryContainer();
        $container->set('config', [
            'dependencies' => [
                'factories' => [
                    ResponseInterface::class => ResponseFactoryFactory::class,
                ],
            ],
        ]);
        $container->set(ResponseFactoryInterface::class, $responseFactory);

        $generator = ($this->factory)($container);
        self::assertSame($responseFactory, $generator->getResponseFactory());
    }

    /**
     * @param array<string,mixed> $config
     * @dataProvider configurationsWithOverriddenResponseInterfaceFactory
     */
    public function testWontUseResponseFactoryInterfaceFromContainerWhenApplicationFactoryIsOverriden(
        array $config
    ): void {
        $responseFactory = $this->createMock(ResponseFactoryInterface::class);
        $container       = new InMemoryContainer();
        $container->set('config', $config);
        $container->set(ResponseFactoryInterface::class, $responseFactory);
        $response = $this->createMock(ResponseInterface::class);
        $container->set(ResponseInterface::class, static fn(): ResponseInterface => $response);

        $generator                    = ($this->factory)($container);
        $responseFactoryFromGenerator = $generator->getResponseFactory();
        self::assertNotSame($responseFactory, $responseFactoryFromGenerator);
        self::assertInstanceOf(CallableResponseFactoryDecorator::class, $responseFactoryFromGenerator);
        self::assertEquals($response, $responseFactoryFromGenerator->getResponseFromCallable());
    }
}
