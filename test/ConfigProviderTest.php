<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest;

use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\ConfigProvider;
use Mezzio\Delegate\NotFoundDelegate;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Middleware;
use Mezzio\Router\Middleware as RouterMiddleware;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $provider;

    public function setUp()
    {
        $this->provider = new ConfigProvider();
    }

    public function testProviderDefinesExpectedAliases()
    {
        $config = $this->provider->getDependencies();
        $aliases = $config['aliases'];
        $this->assertArrayHasKey(Middleware\DispatchMiddleware::class, $aliases);
        $this->assertArrayHasKey(Middleware\ImplicitHeadMiddleware::class, $aliases);
        $this->assertArrayHasKey(Middleware\ImplicitOptionsMiddleware::class, $aliases);
        $this->assertArrayHasKey(Middleware\RouteMiddleware::class, $aliases);
        $this->assertArrayHasKey(NotFoundDelegate::class, $aliases);
        $this->assertArrayHasKey('Mezzio\Delegate\DefaultDelegate', $aliases);
    }

    public function testProviderDefinesExpectedInvokableServices()
    {
        $config = $this->provider->getDependencies();
        $invokables = $config['invokables'];
        $this->assertArrayHasKey(RouterMiddleware\DispatchMiddleware::class, $invokables);
    }

    public function testProviderDefinesExpectedFactoryServices()
    {
        $config = $this->provider->getDependencies();
        $factories = $config['factories'];

        $this->assertArrayHasKey(Application::class, $factories);
        $this->assertArrayHasKey(ErrorHandler::class, $factories);
        $this->assertArrayHasKey(Middleware\ErrorResponseGenerator::class, $factories);
        $this->assertArrayHasKey(Middleware\NotFoundHandler::class, $factories);
        $this->assertArrayHasKey(NotFoundHandler::class, $factories);
        $this->assertArrayHasKey(ResponseInterface::class, $factories);
        $this->assertArrayHasKey(StreamInterface::class, $factories);
        $this->assertArrayHasKey(RouterMiddleware\ImplicitHeadMiddleware::class, $factories);
        $this->assertArrayHasKey(RouterMiddleware\ImplicitOptionsMiddleware::class, $factories);
        $this->assertArrayHasKey(RouterMiddleware\RouteMiddleware::class, $factories);
    }

    public function testInvocationReturnsArrayWithDependencies()
    {
        $config = $this->provider->__invoke();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('aliases', $config['dependencies']);
        $this->assertArrayHasKey('invokables', $config['dependencies']);
        $this->assertArrayHasKey('factories', $config['dependencies']);
    }
}
