<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Diactoros\ServerRequestFactory;
use Psr\Container\ContainerInterface;

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
        return function () {
            return ServerRequestFactory::fromGlobals();
        };
    }
}
