<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Router\DispatchMiddleware;
use Psr\Container\ContainerInterface;

class DispatchMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : DispatchMiddleware
    {
        return new DispatchMiddleware();
    }
}
