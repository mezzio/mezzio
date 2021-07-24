<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Mezzio\ApplicationPipeline;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Create an ApplicationRunner instance.
 *
 * This class consumes two pseudo-services (services that look like class
 * names, but resolve to other artifacts) and two services provided within
 * this package:
 *
 * - Mezzio\ApplicationPipeline, which should resolve to a
 *   Laminas\Stratigility\MiddlewarePipeInterface and/or
 *   Psr\Http\Server\RequestHandlerInterface instance.
 * - Laminas\HttpHandlerRunner\Emitter\EmitterInterface.
 * - Psr\Http\Message\ServerRequestInterface, which should resolve to a PHP
 *   callable that will return a Psr\Http\Message\ServerRequestInterface
 *   instance.
 * - Mezzio\Response\ServerRequestErrorResponseGeneratorFactory,
 */
class RequestHandlerRunnerFactory
{
    public function __invoke(ContainerInterface $container): RequestHandlerRunner
    {
        return new RequestHandlerRunner(
            $container->get(ApplicationPipeline::class),
            $container->get(EmitterInterface::class),
            $container->get(ServerRequestInterface::class),
            $container->get(ServerRequestErrorResponseGenerator::class)
        );
    }
}
