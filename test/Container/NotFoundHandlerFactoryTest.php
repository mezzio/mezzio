<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use ArrayAccess;
use Mezzio\Container\NotFoundHandlerFactory;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Template\TemplateRendererInterface;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class NotFoundHandlerFactoryTest extends TestCase
{
    /** @var InMemoryContainer */
    private $container;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    protected function setUp(): void
    {
        $this->response = $this->createMock(ResponseInterface::class);
        $this->container = new InMemoryContainer();
        $this->container->set(ResponseInterface::class, function () {
            return $this->response;
        });
    }

    public function testFactoryCreatesInstanceWithoutRendererIfRendererServiceIsMissing() : void
    {
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container);

        self::assertEquals(new NotFoundHandler($this->container->get(ResponseInterface::class)), $handler);
    }

    public function testFactoryCreatesInstanceUsingRendererServiceWhenPresent() : void
    {
        $renderer = $this->createMock(TemplateRendererInterface::class);
        $this->container->set(TemplateRendererInterface::class, $renderer);
        $factory = new NotFoundHandlerFactory();

        $handler = $factory($this->container);

        self::assertEquals(new NotFoundHandler($this->container->get(ResponseInterface::class), $renderer), $handler);
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
}
