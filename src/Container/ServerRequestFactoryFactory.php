<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\ServerRequestFilter\FilterServerRequestInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

use function class_exists;
use function sprintf;

/**
 * Return a factory for generating a server request.
 *
 * We cannot return just `ServerRequestFactory::fromGlobals` or
 * `[ServerRequestFactory::class, 'fromGlobals']` as not all containers
 * allow vanilla PHP callable services. Instead, we wrap it in an
 * anonymous function here, which is allowed by all containers tested
 * at this time.
 *
 * This factory consumes the
 * Laminas\Diactoros\ServerRequestFilter\FilterServerRequestInterface
 * service, which is used to make changes when initializing the request.
 */
class ServerRequestFactoryFactory
{
    public function __invoke(ContainerInterface $container): callable
    {
        if (! class_exists(ServerRequestFactory::class)) {
            throw new Exception\InvalidServiceException(sprintf(
                'The %1$s service must map to a factory capable of returning an'
                . ' implementation instance. By default, we assume usage of'
                . ' laminas-diactoros for PSR-7, but it does not appear to be'
                . ' present on your system. Please install laminas/laminas-diactoros'
                . ' or provide an alternate factory for the %1$s service that'
                . ' can produce an appropriate %1$s instance.',
                ServerRequestInterface::class
            ));
        }

        $filter = $container->has(FilterServerRequestInterface::class)
            ? $container->get(FilterServerRequestInterface::class)
            : null;

        return static fn(): ServerRequestInterface => ServerRequestFactory::fromGlobals(requestFilter: $filter);
    }
}
