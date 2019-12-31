<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorHandler;

/**
 * Provide initial configuration for mezzio.
 *
 * This class provides initial _production_ configuration for mezzio.
 */
class ConfigProvider
{
    public function __invoke() : array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies() : array
    {
        // @codingStandardsIgnoreStart
        return [
            'aliases' => [
                Delegate\DefaultDelegate::class      => Middleware\NotFoundMiddleware::class,
                Middleware\DispatchMiddleware::class => Router\DispatchMiddleware::class,
                Middleware\RouteMiddleware::class    => Router\PathBasedRoutingMiddleware::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Delegate\DefaultDelegate::class => Delegate\DefaultDelegate::class,
                \Zend\Expressive\Middleware\DispatchMiddleware::class => Middleware\DispatchMiddleware::class,
                \Zend\Expressive\Middleware\RouteMiddleware::class => Middleware\RouteMiddleware::class,
                \Zend\Expressive\Application::class => Application::class,
                \Zend\Expressive\ApplicationPipeline::class => ApplicationPipeline::class,
                \Zend\HttpHandlerRunner\Emitter\EmitterInterface::class => EmitterInterface::class,
                \Zend\Stratigility\Middleware\ErrorHandler::class => ErrorHandler::class,
                \Zend\Expressive\MiddlewareContainer::class => MiddlewareContainer::class,
                \Zend\Expressive\MiddlewareFactory::class => MiddlewareFactory::class,
                \Zend\Expressive\Middleware\ErrorResponseGenerator::class => Middleware\ErrorResponseGenerator::class,
                \Zend\Expressive\Middleware\NotFoundMiddleware::class => Middleware\NotFoundMiddleware::class,
                \Zend\HttpHandlerRunner\RequestHandlerRunner::class => RequestHandlerRunner::class,
                \Zend\Expressive\Response\NotFoundResponseInterface::class => Response\NotFoundResponseInterface::class,
                \Zend\Expressive\Response\RouterResponseInterface::class => Response\RouterResponseInterface::class,
                \Zend\Expressive\Router\DispatchMiddleware::class => Router\DispatchMiddleware::class,
                \Zend\Expressive\Router\PathBasedRoutingMiddleware::class => Router\PathBasedRoutingMiddleware::class,
                \Zend\Expressive\ServerRequestErrorResponseGenerator::class => ServerRequestErrorResponseGenerator::class,
                \Zend\Expressive\ServerRequestFactory::class => ServerRequestFactory::class,
            ],
            'factories' => [
                Application::class                         => Container\ApplicationFactory::class,
                ApplicationPipeline::class                 => Container\ApplicationPipelineFactory::class,
                EmitterInterface::class                    => Container\EmitterFactory::class,
                ErrorHandler::class                        => Container\ErrorHandlerFactory::class,
                MiddlewareContainer::class                 => Container\MiddlewareContainerFactory::class,
                MiddlewareFactory::class                   => Container\MiddlewareFactoryFactory::class,
                // Change the following in development to the WhoopsErrorResponseGeneratorFactory:
                Middleware\ErrorResponseGenerator::class   => Container\ErrorResponseGeneratorFactory::class,
                Middleware\NotFoundMiddleware::class       => Container\NotFoundMiddlewareFactory::class,
                RequestHandlerRunner::class                => Container\RequestHandlerRunnerFactory::class,
                Response\NotFoundResponseInterface::class  => Container\ResponseFactory::class,
                Response\RouterResponseInterface::class    => Container\ResponseFactory::class,
                Router\DispatchMiddleware::class           => Container\DispatchMiddlewareFactory::class,
                Router\PathBasedRoutingMiddleware::class   => Container\RouteMiddlewareFactory::class,
                ServerRequestErrorResponseGenerator::class => Container\ServerRequestErrorResponseGeneratorFactory::class,
                ServerRequestFactory::class                => Container\ServerRequestFactoryFactory::class,
            ],
        ];
        // @codingStandardsIgnoreEnd
    }
}
