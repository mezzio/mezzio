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

class ServerRequestFactoryFactory
{
    public function __invoke(ContainerInterface $container) : callable
    {
        return [ServerRequestFactory::class, 'fromGlobals'];
    }
}
