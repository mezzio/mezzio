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
use Mezzio\Middleware\ErrorResponseGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Throwable;

class ServerRequestErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : callable
    {
        return function (Throwable $e) use ($container) : ResponseInterface {
            $generator = $container->get(ErrorResponseGenerator::class);
            return $generator($e, new ServerRequest(), new Response());
        };
    }
}
