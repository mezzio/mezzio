<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Laminas\Diactoros\Response;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Middleware\ErrorResponseGenerator;
use Psr\Container\ContainerInterface;

class ErrorHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return ErrorHandler
     */
    public function __invoke(ContainerInterface $container)
    {
        $generator = $container->has(ErrorResponseGenerator::class)
            ? $container->get(ErrorResponseGenerator::class)
            : ($container->has(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)
                ? $container->get(\Zend\Expressive\Middleware\ErrorResponseGenerator::class)
                : null);

        return new ErrorHandler(new Response(), $generator);
    }
}
