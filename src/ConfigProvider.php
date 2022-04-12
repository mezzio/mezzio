<?php

declare(strict_types=1);

namespace Mezzio;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Provide initial configuration for mezzio.
 *
 * This class provides initial _production_ configuration for mezzio.
 */
class ConfigProvider
{
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    public function getDependencies(): array
    {
        // @codingStandardsIgnoreStart
        return [
            'aliases' => [
                DEFAULT_DELEGATE            => Handler\NotFoundHandler::class,
                DISPATCH_MIDDLEWARE         => Router\Middleware\DispatchMiddleware::class,
                IMPLICIT_HEAD_MIDDLEWARE    => Router\Middleware\ImplicitHeadMiddleware::class,
                IMPLICIT_OPTIONS_MIDDLEWARE => Router\Middleware\ImplicitOptionsMiddleware::class,
                NOT_FOUND_MIDDLEWARE        => Handler\NotFoundHandler::class,
                ROUTE_MIDDLEWARE            => Router\Middleware\RouteMiddleware::class,
            ],
            'factories' => [
                Application::class                       => Container\ApplicationFactory::class,
                ApplicationPipeline::class               => Container\ApplicationPipelineFactory::class,
                EmitterInterface::class                  => Container\EmitterFactory::class,
                ErrorHandler::class                      => Container\ErrorHandlerFactory::class,
                Handler\NotFoundHandler::class           => Container\NotFoundHandlerFactory::class,
                MiddlewareContainer::class               => Container\MiddlewareContainerFactory::class,
                MiddlewareFactory::class                 => Container\MiddlewareFactoryFactory::class,
                // Change the following in development to the WhoopsErrorResponseGeneratorFactory:
                Middleware\ErrorResponseGenerator::class => Container\ErrorResponseGeneratorFactory::class,
                RequestHandlerRunner::class              => Container\RequestHandlerRunnerFactory::class,
                ResponseInterface::class                 => Container\ResponseFactoryFactory::class,
                Response\ServerRequestErrorResponseGenerator::class  => Container\ServerRequestErrorResponseGeneratorFactory::class,
                ServerRequestInterface::class            => Container\ServerRequestFactoryFactory::class,
                StreamInterface::class                   => Container\StreamFactoryFactory::class,
            ],
        ];
        // @codingStandardsIgnoreEnd
    }
}
