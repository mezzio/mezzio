<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Mezzio\Delegate\NotFoundDelegate;
use Mezzio\Middleware\NotFoundHandler;
use Psr\Container\ContainerInterface;

class NotFoundHandlerFactory
{
    /**
     * @param ContainerInterface $container
     * @return NotFoundHandler
     */
    public function __invoke(ContainerInterface $container)
    {
        return new NotFoundHandler($container->get(NotFoundDelegate::class));
    }
}
