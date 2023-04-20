<?php

declare(strict_types=1);

namespace Mezzio;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Provide initial configuration for mezzio.
 *
 * This class provides initial _production_ configuration for mezzio.
 *
 * @psalm-import-type ServiceManagerConfigurationType from ConfigInterface
 */
class ConfigProvider
{
    public const DIACTOROS_CONFIG_KEY                       = 'laminas-diactoros';
    public const DIACTOROS_SERVER_REQUEST_FILTER_CONFIG_KEY = 'server-request-filter';
    public const DIACTOROS_X_FORWARDED_FILTER_CONFIG_KEY    = 'x-forwarded-headers';
    public const DIACTOROS_TRUSTED_PROXIES_CONFIG_KEY       = 'trusted-proxies';
    public const DIACTOROS_TRUSTED_HEADERS_CONFIG_KEY       = 'trusted-headers';

    /** @return array{dependencies: ServiceManagerConfigurationType} */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
        ];
    }

    /** @return ServiceManagerConfigurationType */
    public function getDependencies(): array
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        return [
            'aliases'   => [
                DEFAULT_DELEGATE                     => Handler\NotFoundHandler::class,
                DISPATCH_MIDDLEWARE                  => Router\Middleware\DispatchMiddleware::class,
                IMPLICIT_HEAD_MIDDLEWARE             => Router\Middleware\ImplicitHeadMiddleware::class,
                IMPLICIT_OPTIONS_MIDDLEWARE          => Router\Middleware\ImplicitOptionsMiddleware::class,
                NOT_FOUND_MIDDLEWARE                 => Handler\NotFoundHandler::class,
                ROUTE_MIDDLEWARE                     => Router\Middleware\RouteMiddleware::class,
                RequestHandlerRunnerInterface::class => RequestHandlerRunner::class,
                MiddlewareFactoryInterface::class    => MiddlewareFactory::class,

                // Legacy Zend Framework aliases
                'Zend\Expressive\Application'                                  => Application::class,
                'Zend\Expressive\ApplicationPipeline'                          => 'Mezzio\ApplicationPipeline',
                'Zend\HttpHandlerRunner\Emitter\EmitterInterface'              => EmitterInterface::class,
                'Zend\Stratigility\Middleware\ErrorHandler'                    => ErrorHandler::class,
                'Zend\Expressive\Handler\NotFoundHandler'                      => Handler\NotFoundHandler::class,
                'Zend\Expressive\MiddlewareContainer'                          => MiddlewareContainer::class,
                'Zend\Expressive\MiddlewareFactory'                            => MiddlewareFactory::class,
                'Zend\Expressive\Middleware\ErrorResponseGenerator'            => Middleware\ErrorResponseGenerator::class,
                'Zend\HttpHandlerRunner\RequestHandlerRunner'                  => RequestHandlerRunner::class,
                'Zend\Expressive\Response\ServerRequestErrorResponseGenerator' => Response\ServerRequestErrorResponseGenerator::class,
            ],
            'factories' => [
                Application::class             => Container\ApplicationFactory::class,
                'Mezzio\ApplicationPipeline'   => Container\ApplicationPipelineFactory::class,
                EmitterInterface::class        => Container\EmitterFactory::class,
                ErrorHandler::class            => Container\ErrorHandlerFactory::class,
                Handler\NotFoundHandler::class => Container\NotFoundHandlerFactory::class,
                MiddlewareContainer::class     => Container\MiddlewareContainerFactory::class,
                MiddlewareFactory::class       => Container\MiddlewareFactoryFactory::class,
                // Change the following in development to the WhoopsErrorResponseGeneratorFactory:
                Middleware\ErrorResponseGenerator::class            => Container\ErrorResponseGeneratorFactory::class,
                RequestHandlerRunner::class                         => Container\RequestHandlerRunnerFactory::class,
                ResponseInterface::class                            => Container\ResponseFactoryFactory::class,
                Response\ServerRequestErrorResponseGenerator::class => Container\ServerRequestErrorResponseGeneratorFactory::class,
                ServerRequestInterface::class                       => Container\ServerRequestFactoryFactory::class,
                StreamInterface::class                              => Container\StreamFactoryFactory::class,
            ],
        ];
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
