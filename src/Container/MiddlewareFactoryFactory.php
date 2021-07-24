<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Psr\Container\ContainerInterface;

class MiddlewareFactoryFactory
{
    public function __invoke(ContainerInterface $container): MiddlewareFactory
    {
        return new MiddlewareFactory(
            $container->get(MiddlewareContainer::class)
        );
    }
}
