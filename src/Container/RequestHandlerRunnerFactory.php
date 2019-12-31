<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\ApplicationPipeline;
use Mezzio\ServerRequestErrorResponseGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Create an ApplicationRunner instance.
 *
 * This class consumes three pseudo-services (services that look like class
 * names, but resolve to other artifacts):
 *
 * - Mezzio\ApplicationPipeline, which should resolve to a
 *   Laminas\Stratigility\MiddlewarePipeInterface and/or
 *   Psr\Http\Server\RequestHandlerInterface instance.
 * - Psr\Http\Message\ServerRequestInterface, which should resolve to a PHP
 *   callable that will return a Psr\Http\Message\ServerRequestInterface
 *   instance.
 * - Mezzio\ServerRequestErrorResponseGenerator, which should resolve
 *   to a PHP callable that accepts a Throwable argument, and which will return
 *   a Psr\Http\Message\ResponseInterface instance.
 *
 * It also consumes the service Laminas\HttpHandlerRunner\Emitter\EmitterInterface.
 */
class RequestHandlerRunnerFactory
{
    public function __invoke(ContainerInterface $container) : RequestHandlerRunner
    {
        return new RequestHandlerRunner(
            $container->get(ApplicationPipeline::class),
            $container->get(EmitterInterface::class),
            $container->get(ServerRequestInterface::class),
            $container->get(ServerRequestErrorResponseGenerator::class)
        );
    }
}
