<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\NotFoundMiddlewareFactory;
use Mezzio\Middleware\NotFoundMiddleware;
use Mezzio\Response\NotFoundResponseInterface;
use Mezzio\Template\TemplateRendererInterface;
use PHPUnit\Framework\TestCase;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class NotFoundMiddlewareFactoryTest extends TestCase
{
    /** @var ContainerInterface|ObjectProphecy */
    private $container;

    /** @var ResponseInterface|ObjectProphecy */
    private $response;

    protected function setUp()
    {
        $this->response = $this->prophesize(ResponseInterface::class)->reveal();
        $this->container = $this->prophesize(ContainerInterface::class);
        $this->container->get(NotFoundResponseInterface::class)->willReturn($this->response);
    }

    public function testFactoryCreatesInstanceWithoutRendererIfRendererServiceIsMissing()
    {
        $this->container->has('config')->willReturn(false);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new NotFoundMiddlewareFactory();

        $middleware = $factory($this->container->reveal());
        $this->assertInstanceOf(NotFoundMiddleware::class, $middleware);
        $this->assertAttributeSame($this->response, 'responsePrototype', $middleware);
        $this->assertAttributeEmpty('renderer', $middleware);
    }

    public function testFactoryCreatesInstanceUsingRendererServiceWhenPresent()
    {
        $renderer = $this->prophesize(TemplateRendererInterface::class)->reveal();
        $this->container->has('config')->willReturn(false);
        $this->container->has(TemplateRendererInterface::class)->willReturn(true);
        $this->container->get(TemplateRendererInterface::class)->willReturn($renderer);
        $factory = new NotFoundMiddlewareFactory();

        $middleware = $factory($this->container->reveal());
        $this->assertAttributeSame($renderer, 'renderer', $middleware);
    }

    public function testFactoryUsesConfigured404TemplateWhenPresent()
    {
        $config = [
            'mezzio' => [
                'error_handler' => [
                    'layout' => 'layout::error',
                    'template_404' => 'foo::bar',
                ],
            ],
        ];
        $this->container->has('config')->willReturn(true);
        $this->container->get('config')->willReturn($config);
        $this->container->has(TemplateRendererInterface::class)->willReturn(false);
        $this->container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)->willReturn(false);
        $factory = new NotFoundMiddlewareFactory();

        $middleware = $factory($this->container->reveal());
        $this->assertAttributeEquals(
            $config['mezzio']['error_handler']['layout'],
            'layout',
            $middleware
        );
        $this->assertAttributeEquals(
            $config['mezzio']['error_handler']['template_404'],
            'template',
            $middleware
        );
    }
}
