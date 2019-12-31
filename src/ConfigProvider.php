<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Provide initial configuration for mezzio.
 *
 * This class provides initial _production_ configuration for mezzio.
 */
class ConfigProvider
{
    /**
     * @return array
     */
    public function __invoke()
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /**
     * @return array
     */
    public function getDependencies()
    {
        // @codingStandardsIgnoreStart
        return [
            'aliases' => [
                Delegate\NotFoundDelegate::class            => Handler\NotFoundHandler::class,
                Middleware\DispatchMiddleware::class        => Router\Middleware\DispatchMiddleware::class,
                Middleware\ImplicitHeadMiddleware::class    => Router\Middleware\ImplicitHeadMiddleware::class,
                Middleware\ImplicitOptionsMiddleware::class => Router\Middleware\ImplicitOptionsMiddleware::class,
                Middleware\RouteMiddleware::class           => Router\Middleware\RouteMiddleware::class,
                'Mezzio\Delegate\DefaultDelegate'  => Handler\NotFoundHandler::class,

                // Legacy Zend Framework aliases
                \Zend\Expressive\Delegate\NotFoundDelegate::class => Delegate\NotFoundDelegate::class,
                \Zend\Expressive\Middleware\DispatchMiddleware::class => Middleware\DispatchMiddleware::class,
                \Zend\Expressive\Middleware\ImplicitHeadMiddleware::class => Middleware\ImplicitHeadMiddleware::class,
                \Zend\Expressive\Middleware\ImplicitOptionsMiddleware::class => Middleware\ImplicitOptionsMiddleware::class,
                \Zend\Expressive\Middleware\RouteMiddleware::class => Middleware\RouteMiddleware::class,
                'Zend\Expressive\Delegate\DefaultDelegate' => 'Mezzio\Delegate\DefaultDelegate',
                \Zend\Expressive\Application::class => Application::class,
                \Zend\Stratigility\Middleware\ErrorHandler::class => ErrorHandler::class,
                \Zend\Expressive\Handler\NotFoundHandler::class => Handler\NotFoundHandler::class,
                \Zend\Expressive\Middleware\ErrorResponseGenerator::class => Middleware\ErrorResponseGenerator::class,
                \Zend\Expressive\Middleware\NotFoundHandler::class => Middleware\NotFoundHandler::class,
                \Zend\Expressive\Router\Middleware\DispatchMiddleware::class => Router\Middleware\DispatchMiddleware::class,
                \Zend\Expressive\Router\Middleware\ImplicitHeadMiddleware::class => Router\Middleware\ImplicitHeadMiddleware::class,
                \Zend\Expressive\Router\Middleware\ImplicitOptionsMiddleware::class => Router\Middleware\ImplicitOptionsMiddleware::class,
                \Zend\Expressive\Router\Middleware\RouteMiddleware::class => Router\Middleware\RouteMiddleware::class,
            ],
            'factories' => [
                Application::class                       => Container\ApplicationFactory::class,
                ErrorHandler::class                      => Container\ErrorHandlerFactory::class,
                Handler\NotFoundHandler::class           => Container\NotFoundDelegateFactory::class,
                // Change the following in development to the WhoopsErrorResponseGeneratorFactory:
                Middleware\ErrorResponseGenerator::class => Container\ErrorResponseGeneratorFactory::class,
                Middleware\NotFoundHandler::class        => Container\NotFoundHandlerFactory::class,
                ResponseInterface::class                 => Container\ResponseFactoryFactory::class,
                StreamInterface::class                   => Container\StreamFactoryFactory::class,

                // These are duplicates, in case the mezzio-router package ConfigProvider is not wired:
                Router\Middleware\DispatchMiddleware::class        => Router\Middleware\DispatchMiddlewareFactory::class,
                Router\Middleware\ImplicitHeadMiddleware::class    => Router\Middleware\ImplicitHeadMiddlewareFactory::class,
                Router\Middleware\ImplicitOptionsMiddleware::class => Router\Middleware\ImplicitOptionsMiddlewareFactory::class,
                Router\Middleware\RouteMiddleware::class           => Router\Middleware\RouteMiddlewareFactory::class,
            ],
        ];
        // @codingStandardsIgnoreEnd
    }
}
