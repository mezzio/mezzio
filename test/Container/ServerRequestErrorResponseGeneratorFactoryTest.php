<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use ArrayAccess;
use Generator;
use Laminas\Diactoros\Response\TextResponse;
use Mezzio\Container\ResponseFactoryFactory;
use Mezzio\Container\ServerRequestErrorResponseGeneratorFactory;
use Mezzio\Response\CallableResponseFactoryDecorator;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

class ServerRequestErrorResponseGeneratorFactoryTest extends TestCase
{
    private ServerRequestErrorResponseGeneratorFactory $factory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->factory = new ServerRequestErrorResponseGeneratorFactory();
    }

    /**
     * @psalm-return Generator<non-empty-string,array{0:array<string,mixed>}>
     */
    public static function configurationsWithOverriddenResponseInterfaceFactory(): Generator
    {
        yield 'default' => [
            [
                'dependencies' => [
                    'factories' => [
                        ResponseInterface::class
                            => fn(): ResponseInterface => new TextResponse('Foo'),
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
                            fn(): ResponseInterface => new TextResponse('Foo'),
                        ],
                    ],
                ],
            ],
        ];
    }

    public function testFactoryOnlyRequiresResponseService(): void
    {
        $container = new InMemoryContainer();

        $this->expectException(RuntimeException::class);
        ($this->factory)($container);
    }

    public function testFactoryCreatesGeneratorWhenOnlyResponseServiceIsPresent(): void
    {
        $container = new InMemoryContainer();

        $responseFactory = fn(): ResponseInterface => $this->createMock(ResponseInterface::class);
        $container->set(ResponseInterface::class, $responseFactory);

        $generator = ($this->factory)($container);

        self::assertEquals(new ServerRequestErrorResponseGenerator($responseFactory), $generator);
    }

    public function testFactoryCreatesGeneratorUsingConfiguredServices(): void
    {
        $config   = [
            'debug'  => true,
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

        $responseFactory = fn(): ResponseInterface => $this->createMock(ResponseInterface::class);
        $container->set(ResponseInterface::class, $responseFactory);

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
        $config    = $this->createMock(ArrayAccess::class);
        $container = new InMemoryContainer();
        $container->set('config', $config);
        $responseFactory = static function (): void {
        };
        $container->set(ResponseInterface::class, $responseFactory);

        ($this->factory)($container);
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
     */
    #[DataProvider('configurationsWithOverriddenResponseInterfaceFactory')]
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
