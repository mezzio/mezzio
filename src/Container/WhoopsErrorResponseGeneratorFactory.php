<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Middleware\WhoopsErrorResponseGenerator;
use Psr\Container\ContainerInterface;

class WhoopsErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container): WhoopsErrorResponseGenerator
    {
        return new WhoopsErrorResponseGenerator(
            $container->get('Mezzio\Whoops')
        );
    }
}
