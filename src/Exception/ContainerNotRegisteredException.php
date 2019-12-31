<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Exception;

use RuntimeException;

class ContainerNotRegisteredException extends RuntimeException implements ExceptionInterface
{
    public static function forMiddlewareService(string $middleware) : self
    {
        return new self(sprintf(
            'Cannot marshal middleware by service name "%s"; no container registered',
            $middleware
        ));
    }
}
