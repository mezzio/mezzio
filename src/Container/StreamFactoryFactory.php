<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Laminas\Diactoros\Stream;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Produces a callable capable of producing an empty stream for use with
 * services that need to produce a stream for use with a request or a response.
 */
class StreamFactoryFactory
{
    /**
     * @return callable
     */
    public function __invoke(ContainerInterface $container)
    {
        return function () {
            return new Stream('php://temp', 'wb+');
        };
    }
}
