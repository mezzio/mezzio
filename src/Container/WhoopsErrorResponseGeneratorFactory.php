<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Mezzio\Middleware\WhoopsErrorResponseGenerator;
use Psr\Container\ContainerInterface;

class WhoopsErrorResponseGeneratorFactory
{
    /**
     * @param ContainerInterface $container
     * @return WhoopsErrorResponseGenerator
     */
    public function __invoke(ContainerInterface $container)
    {
        return new WhoopsErrorResponseGenerator(
            $container->get('Mezzio\Whoops')
        );
    }
}
