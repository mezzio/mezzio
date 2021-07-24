<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Middleware\ErrorResponseGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class ErrorHandlerFactory
{
    public function __invoke(ContainerInterface $container): ErrorHandler
    {
        $generator = $container->has(ErrorResponseGenerator::class)
            ? $container->get(ErrorResponseGenerator::class)
            : ($container->has(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)
                ? $container->get(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)
                : null);

        return new ErrorHandler($container->get(ResponseInterface::class), $generator);
    }
}
