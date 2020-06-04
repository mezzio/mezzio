<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Laminas\HttpHandlerRunner\Emitter\SapiStreamEmitter;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\MiddlewarePipe;
use Mezzio\Application;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Router\RouteCollector;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

class SimpleApplicationFactory
{
    /**
     * Create a {@see Application} instance in a simpler way by assuming defaults for most dependencies. Using
     * this method of instantiating the application assumes:
     *
     *  - You have `laminas/diactoros` installed
     *  - You do not need the {@see SapiStreamEmitter}
     *  - Your {@see ServerRequest} comes from {@see $_SERVER}
     *
     * All you need to provide is:
     *
     *  - A container instance implementing {@see ContainerInterface}, for example Laminas ServiceManager
     *  - A router instance implementing {@see RouterInterface}, for example FastRoute
     *
     * @example
     *     $container = new \Laminas\ServiceManager\ServiceManager();
     *     $router = new \Mezzio\Router\FastRouteRouter();
     *     $app = \Mezzio\Container\SimpleApplicationFactory::create($container, $router);
     *     $app->pipe(new \Mezzio\Router\Middleware\RouteMiddleware($router));
     *     $app->pipe(new \Mezzio\Router\Middleware\DispatchMiddleware());
     *     $app->get('/hello-world', new class implements \Psr\Http\Server\RequestHandlerInterface {
     *         public function handle(
     *             \Psr\Http\Message\ServerRequestInterface $request
     *         ): \Psr\Http\Message\ResponseInterface {
     *             return new \Laminas\Diactoros\Response\TextResponse('hello world!');
     *         }
     *     });
     *     $app->run();
     */
    public static function create(
        ContainerInterface $container,
        RouterInterface $router,
        bool $developmentMode = false
    ) : Application {
        $middlewarePipe = new MiddlewarePipe();
        $app = new Application(
            new MiddlewareFactory(new MiddlewareContainer($container)),
            $middlewarePipe,
            new RouteCollector($router),
            new RequestHandlerRunner(
                $middlewarePipe,
                new SapiEmitter(),
                [ServerRequestFactory::class, 'fromGlobals'],
                new ServerRequestErrorResponseGenerator(
                    function () : Response {
                        return new Response();
                    },
                    $developmentMode
                )
            )
        );

        return $app;
    }
}
