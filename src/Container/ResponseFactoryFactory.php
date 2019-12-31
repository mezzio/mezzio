<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Laminas\Diactoros\Response;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Produces a callable capable of producing a response prototype for use with
 * services that need to produce a response.
 */
class ResponseFactoryFactory
{
    /**
     * @return callable
     */
    public function __invoke(ContainerInterface $container)
    {
        return function () {
            return new Response();
        };
    }
}
