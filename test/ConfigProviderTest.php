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
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Middleware;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\ServerRequestErrorResponseGenerator;
use Mezzio\ServerRequestFactory;
use PHPUnit\Framework\TestCase;

use const Mezzio\DEFAULT_DELEGATE;
use const Mezzio\DISPATCH_MIDDLEWARE;
use const Mezzio\IMPLICIT_HEAD_MIDDLEWARE;
use const Mezzio\IMPLICIT_OPTIONS_MIDDLEWARE;
use const Mezzio\NOT_FOUND_MIDDLEWARE;
use const Mezzio\NOT_FOUND_RESPONSE;
use const Mezzio\ROUTE_MIDDLEWARE;
use const Mezzio\Router\IMPLICIT_HEAD_MIDDLEWARE_RESPONSE;
use const Mezzio\Router\IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY;
use const Mezzio\Router\IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE;
use const Mezzio\Router\METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE;
use const Mezzio\SERVER_REQUEST_ERROR_RESPONSE_GENERATOR;
use const Mezzio\SERVER_REQUEST_FACTORY;

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
        $this->assertArrayHasKey(DEFAULT_DELEGATE, $aliases);
        $this->assertArrayHasKey(DISPATCH_MIDDLEWARE, $aliases);
        $this->assertArrayHasKey(IMPLICIT_HEAD_MIDDLEWARE, $aliases);
        $this->assertArrayHasKey(IMPLICIT_OPTIONS_MIDDLEWARE, $aliases);
        $this->assertArrayHasKey(NOT_FOUND_MIDDLEWARE, $aliases);
        $this->assertArrayHasKey(ROUTE_MIDDLEWARE, $aliases);
    }

    public function testProviderDefinesExpectedFactoryServices()
    {
        $config = $this->provider->getDependencies();
        $factories = $config['factories'];

        $this->assertArrayHasKey(Application::class, $factories);
        $this->assertArrayHasKey(ApplicationPipeline::class, $factories);
        $this->assertArrayHasKey(EmitterInterface::class, $factories);
        $this->assertArrayHasKey(ErrorHandler::class, $factories);
        $this->assertArrayHasKey(IMPLICIT_HEAD_MIDDLEWARE_RESPONSE, $factories);
        $this->assertArrayHasKey(IMPLICIT_HEAD_MIDDLEWARE_STREAM_FACTORY, $factories);
        $this->assertArrayHasKey(IMPLICIT_OPTIONS_MIDDLEWARE_RESPONSE, $factories);
        $this->assertArrayHasKey(METHOD_NOT_ALLOWED_MIDDLEWARE_RESPONSE, $factories);
        $this->assertArrayHasKey(MiddlewareContainer::class, $factories);
        $this->assertArrayHasKey(MiddlewareFactory::class, $factories);
        $this->assertArrayHasKey(Middleware\ErrorResponseGenerator::class, $factories);
        $this->assertArrayHasKey(NotFoundHandler::class, $factories);
        $this->assertArrayHasKey(NOT_FOUND_RESPONSE, $factories);
        $this->assertArrayHasKey(RequestHandlerRunner::class, $factories);
        $this->assertArrayHasKey(SERVER_REQUEST_ERROR_RESPONSE_GENERATOR, $factories);
        $this->assertArrayHasKey(SERVER_REQUEST_FACTORY, $factories);
    }

    public function testInvocationReturnsArrayWithDependencies()
    {
        $config = ($this->provider)();
        $this->assertInternalType('array', $config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('aliases', $config['dependencies']);
        $this->assertArrayHasKey('factories', $config['dependencies']);
    }
}
