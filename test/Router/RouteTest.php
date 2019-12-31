<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Router;

use Mezzio\Router\Route;
use PHPUnit_Framework_TestCase as TestCase;

class RouteTest extends TestCase
{
    public function setUp()
    {
        $this->noopMiddleware = function ($req, $res, $next) {
        };
    }

    public function testRoutePathIsRetrievable()
    {
        $route = new Route('/foo', $this->noopMiddleware);
        $this->assertEquals('/foo', $route->getPath());
    }

    public function testRouteMiddlewareIsRetrievable()
    {
        $route = new Route('/foo', $this->noopMiddleware);
        $this->assertSame($this->noopMiddleware, $route->getMiddleware());
    }

    public function testRouteMiddlewareMayBeANonCallableString()
    {
        $route = new Route('/foo', 'Application\Middleware\HelloWorld');
        $this->assertSame('Application\Middleware\HelloWorld', $route->getMiddleware());
    }

    public function testRouteInstanceAcceptsAllHttpMethodsByDefault()
    {
        $route = new Route('/foo', $this->noopMiddleware);
        $this->assertSame(Route::HTTP_METHOD_ANY, $route->getAllowedMethods());
    }

    public function testRouteAllowsSpecifyingHttpMethods()
    {
        $methods = ['GET', 'POST'];
        $route = new Route('/foo', $this->noopMiddleware, $methods);
        $this->assertSame($methods, $route->getAllowedMethods($methods));
    }

    public function testRouteCanMatchMethod()
    {
        $methods = ['GET', 'POST'];
        $route = new Route('/foo', $this->noopMiddleware, $methods);
        $this->assertTrue($route->allowsMethod('GET'));
        $this->assertTrue($route->allowsMethod('POST'));
        $this->assertFalse($route->allowsMethod('PATCH'));
        $this->assertFalse($route->allowsMethod('DELETE'));
    }

    public function testRouteAlwaysAllowsHeadMethod()
    {
        $route = new Route('/foo', $this->noopMiddleware, []);
        $this->assertTrue($route->allowsMethod('HEAD'));
    }

    public function testRouteAlwaysAllowsOptionsMethod()
    {
        $route = new Route('/foo', $this->noopMiddleware, []);
        $this->assertTrue($route->allowsMethod('OPTIONS'));
    }

    public function testRouteAllowsSpecifyingOptions()
    {
        $options = ['foo' => 'bar'];
        $route = new Route('/foo', $this->noopMiddleware);
        $route->setOptions($options);
        $this->assertSame($options, $route->getOptions());
    }

    public function testRouteOptionsAreEmptyByDefault()
    {
        $route = new Route('/foo', $this->noopMiddleware);
        $this->assertSame([], $route->getOptions());
    }

    public function testRouteNameForRouteAcceptingAnyMethodMatchesPathByDefault()
    {
        $route = new Route('/test', $this->noopMiddleware);
        $this->assertSame('/test', $route->getName());
    }

    public function testRouteNameWithConstructor()
    {
        $route = new Route('/test', $this->noopMiddleware, [], 'test');
        $this->assertSame('test', $route->getName());
    }

    public function testRouteNameWithGET()
    {
        $route = new Route('/test', $this->noopMiddleware, [ 'GET' ]);
        $this->assertSame('/test^GET', $route->getName());
    }

    public function testRouteNameWithGetAndPost()
    {
        $route = new Route('/test', $this->noopMiddleware, [ 'GET', 'POST' ]);
        $this->assertSame('/test^GET' . Route::HTTP_METHOD_SEPARATOR . 'POST', $route->getName());
    }
}
