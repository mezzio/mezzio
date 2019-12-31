<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use ArrayObject;
use Laminas\Diactoros\Response\EmitterInterface;
use Mezzio\Application;
use Mezzio\Delegate;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\RouterInterface;
use Psr\Container\ContainerInterface;

/**
 * Factory to use with an IoC container in order to return an Application instance.
 *
 * This factory uses the following services, if available:
 *
 * - 'Mezzio\Router\RouterInterface'. If missing, a FastRoute router
 *   bridge will be instantiated and used.
 * - 'Mezzio\Delegate\DefaultDelegate'. The service should be
 *   either a `Interop\Http\ServerMiddleware\DelegateInterface` instance, or
 *   a callable that accepts a request and optionally a response; the instance
 *   will be used as the default delegate when the middleware pipeline is
 *   exhausted. If none is provided, `Mezzio\Application` will create
 *   a `Mezzio\Delegate\NotFoundDelegate` instance using the response
 *   prototype only.
 * - 'Laminas\Diactoros\Response\EmitterInterface'. If missing, an EmitterStack is
 *   created, adding a SapiEmitter to the bottom of the stack.
 * - 'config' (an array or ArrayAccess object). If present, and it contains route
 *   definitions, these will be used to seed routes in the Application instance
 *   before returning it.
 *
 * Please see `Mezzio\ApplicationConfigInjectionTrait` for details on how
 * to provide routing and middleware pipeline configuration; this factory
 * delegates to the methods in that trait in order to seed the
 * `Application` instance (which composes the trait).
 *
 * You may disable injection of configured routing and the middleware pipeline
 * by enabling the `mezzio.programmatic_pipeline` configuration flag.
 */
class ApplicationFactory
{
    const DISPATCH_MIDDLEWARE = Application::DISPATCH_MIDDLEWARE;
    const ROUTING_MIDDLEWARE = Application::ROUTING_MIDDLEWARE;

    /**
     * Create and return an Application instance.
     *
     * See the class level docblock for information on what services this
     * factory will optionally consume.
     *
     * @param ContainerInterface $container
     * @return Application
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = $config instanceof ArrayObject ? $config->getArrayCopy() : $config;

        $router = $container->has(RouterInterface::class)
            ? $container->get(RouterInterface::class)
            : ($container->has(\Zend\Expressive\Router\RouterInterface::class)
                ? $container->get(\Zend\Expressive\Router\RouterInterface::class)
                : new FastRouteRouter());

        $delegate = $container->has(Delegate\DefaultDelegate::class)
            ? $container->get(Delegate\DefaultDelegate::class)
            : ($container->has(\Zend\Expressive\Delegate\DefaultDelegate::class)
                ? $container->get(\Zend\Expressive\Delegate\DefaultDelegate::class)
                : null);

        $emitter = $container->has(EmitterInterface::class)
            ? $container->get(EmitterInterface::class)
            : ($container->has(\Zend\Diactoros\Response\EmitterInterface::class)
                ? $container->get(\Zend\Diactoros\Response\EmitterInterface::class)
                : null);

        $app = new Application($router, $container, $delegate, $emitter);

        if (empty($config['mezzio']['programmatic_pipeline'])) {
            $this->injectRoutesAndPipeline($app, $config);
        }

        return $app;
    }

    /**
     * Injects routes and the middleware pipeline into the application.
     *
     * @param Application $app
     * @param array $config
     * @return void
     */
    private function injectRoutesAndPipeline(Application $app, array $config)
    {
        $app->injectRoutesFromConfig($config);
        $app->injectPipelineFromConfig($config);
    }
}
