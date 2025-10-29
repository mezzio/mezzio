<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Response\CallableResponseFactoryDecorator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/** @final */
class ErrorHandlerFactory
{
    public function __invoke(ContainerInterface $container): ErrorHandler
    {
        $generator = $container->has(ErrorResponseGenerator::class)
            ? $container->get(ErrorResponseGenerator::class)
            : ($container->has(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)
                ? $container->get(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)
                : null);

        /** @var callable():ResponseInterface $responseFactory */
        $responseFactory = $container->get(ResponseInterface::class);

        return new ErrorHandler(new CallableResponseFactoryDecorator($responseFactory), $generator);
    }
}
