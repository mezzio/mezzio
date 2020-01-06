<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Middleware\WhoopsErrorResponseGenerator;
use Psr\Container\ContainerInterface;

class WhoopsErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : WhoopsErrorResponseGenerator
    {
        return new WhoopsErrorResponseGenerator(
            $container->get('Mezzio\Whoops')
        );
    }
}
