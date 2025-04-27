<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\MiddlewareContainer;
use Psr\Container\ContainerInterface;

/** @final */
class MiddlewareContainerFactory
{
    public function __invoke(ContainerInterface $container): MiddlewareContainer
    {
        return new MiddlewareContainer($container);
    }
}
