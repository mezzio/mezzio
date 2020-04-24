<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\ServerRequestFactory;
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
 */
class ServerRequestFactoryFactory
{
    public function __invoke(ContainerInterface $container) : callable
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

        return static function () : ServerRequest {
            return ServerRequestFactory::fromGlobals();
        };
    }
}
