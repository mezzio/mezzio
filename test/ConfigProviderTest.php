<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\ApplicationPipeline;
use Mezzio\ConfigProvider;
use Mezzio\Delegate\DefaultDelegate;
use Mezzio\Middleware;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Router\DispatchMiddleware;
use Mezzio\Router\PathBasedRoutingMiddleware;
use Mezzio\ServerRequestErrorResponseGenerator;
use Mezzio\ServerRequestFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;

class ConfigProviderTest extends TestCase
{
    public function setUp()
    {
        $this->provider = new ConfigProvider();
    }

    public function testProviderDefinesExpectedAliases()
    {
        $config = $this->provider->getDependencies();
        $aliases = $config['aliases'];
        $this->assertArrayHasKey(DefaultDelegate::class, $aliases);
        $this->assertArrayHasKey(Middleware\DispatchMiddleware::class, $aliases);
        $this->assertArrayHasKey(Middleware\RouteMiddleware::class, $aliases);
    }

    public function testProviderDefinesExpectedFactoryServices()
    {
        $config = $this->provider->getDependencies();
        $factories = $config['factories'];

        $this->assertArrayHasKey(Application::class, $factories);
        $this->assertArrayHasKey(ApplicationPipeline::class, $factories);
        $this->assertArrayHasKey(EmitterInterface::class, $factories);
        $this->assertArrayHasKey(ErrorHandler::class, $factories);
        $this->assertArrayHasKey(MiddlewareContainer::class, $factories);
        $this->assertArrayHasKey(MiddlewareFactory::class, $factories);
        $this->assertArrayHasKey(DispatchMiddleware::class, $factories);
        $this->assertArrayHasKey(Middleware\ErrorResponseGenerator::class, $factories);
        $this->assertArrayHasKey(Middleware\NotFoundMiddleware::class, $factories);
        $this->assertArrayHasKey(PathBasedRoutingMiddleware::class, $factories);
        $this->assertArrayHasKey(RequestHandlerRunner::class, $factories);
        $this->assertArrayHasKey(ResponseInterface::class, $factories);
        $this->assertArrayHasKey(ServerRequestErrorResponseGenerator::class, $factories);
        $this->assertArrayHasKey(ServerRequestFactory::class, $factories);
    }

    public function testResponseIsMarkedAsUnshared()
    {
        $config = $this->provider->getDependencies();
        $shared = $config['shared'];

        $this->assertArrayHasKey(ResponseInterface::class, $shared);
        $this->assertFalse($shared[ResponseInterface::class]);
    }

    public function testInvocationReturnsArrayWithDependencies()
    {
        $config = ($this->provider)();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('aliases', $config['dependencies']);
        $this->assertArrayHasKey('factories', $config['dependencies']);
        $this->assertArrayHasKey('shared', $config['dependencies']);
    }
}
