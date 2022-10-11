<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Diactoros\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

use function class_exists;
use function sprintf;

/**
 * Produces a callable capable of producing a response prototype for use with
 * services that need to produce a response.
 */
class ResponseFactoryFactory
{
    public function __invoke(ContainerInterface $container): callable
    {
        if (! class_exists(Response::class)) {
            throw new Exception\InvalidServiceException(sprintf(
                'The %1$s service must map to a factory capable of returning an'
                . ' implementation instance. By default, we assume usage of'
                . ' laminas-diactoros for PSR-7, but it does not appear to be'
                . ' present on your system. Please install laminas/laminas-diactoros'
                . ' or provide an alternate factory for the %1$s service that'
                . ' can produce an appropriate %1$s instance.',
                ResponseInterface::class
            ));
        }

        return static fn(): Response => new Response();
    }
}
