<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio;

use Laminas\Diactoros\Response\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Laminas\Stratigility\Middleware\ErrorResponseGenerator;
use Psr\Http\Message\ResponseInterface;

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
                Handler\DefaultHandler::class        => Handler\NotFoundHandler::class,
                Middleware\DispatchMiddleware::class => Router\DispatchMiddleware::class,
                Middleware\RouteMiddleware::class    => Router\PathBasedRoutingMiddleware::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Handler\DefaultHandler::class => Handler\DefaultHandler::class,
                \Zend\Expressive\Middleware\DispatchMiddleware::class => Middleware\DispatchMiddleware::class,
                \Zend\Expressive\Middleware\RouteMiddleware::class => Middleware\RouteMiddleware::class,
                \Zend\Expressive\Application::class => Application::class,
                \Zend\Expressive\ApplicationPipeline::class => ApplicationPipeline::class,
                \Zend\Diactoros\Response\EmitterInterface::class => EmitterInterface::class,
                \Zend\Stratigility\Middleware\ErrorHandler::class => ErrorHandler::class,
                \Zend\Stratigility\Middleware\ErrorResponseGenerator::class => ErrorResponseGenerator::class,
                \Zend\Expressive\Handler\NotFoundHandler::class => Handler\NotFoundHandler::class,
                \Zend\Expressive\MiddlewareContainer::class => MiddlewareContainer::class,
                \Zend\Expressive\MiddlewareFactory::class => MiddlewareFactory::class,
                \Zend\Expressive\Middleware\NotFoundMiddleware::class => Middleware\NotFoundMiddleware::class,
                \Zend\HttpHandlerRunner\RequestHandlerRunner::class => RequestHandlerRunner::class,
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
                // Change the following in development to the WhoopsErrorResponseGeneratorFactory:
                ErrorResponseGenerator::class              => Container\ErrorResponseGeneratorFactory::class,
                Handler\NotFoundHandler::class             => Handler\NotFoundHandlerFactory::class,
                MiddlewareContainer::class                 => Container\MiddlewareContainerFactory::class,
                MiddlewareFactory::class                   => Container\MiddlewareFactoryFactory::class,
                Middleware\NotFoundMiddleware::class       => Container\NotFoundMiddlewareFactory::class,
                RequestHandlerRunner::class                => Container\RequestHandlerRunnerFactory::class,
                ResponseInterface::class                   => Container\ResponseFactory::class,
                Router\DispatchMiddleware::class           => Container\DispatchMiddlewareFactory::class,
                Router\PathBasedRoutingMiddleware::class   => Container\RouteMiddlewareFactory::class,
                ServerRequestErrorResponseGenerator::class => Container\ServerRequestErrorResponseGeneratorFactory::class,
                ServerRequestFactory::class                => Container\ServerRequestFactoryFactory::class,
            ],
            'shared' => [
                // Do not share response instances
                ResponseInterface::class => false,
            ],
        ];
        // @codingStandardsIgnoreEnd
    }
}
