<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Handler\NotFoundHandler;
use Mezzio\Middleware\NotFoundMiddleware;
use Psr\Container\ContainerInterface;

class NotFoundMiddlewareFactory
{
    public function __invoke(ContainerInterface $container) : NotFoundMiddleware
    {
        return new NotFoundMiddleware($container->get(NotFoundHandler::class));
    }
}
