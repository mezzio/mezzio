<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Exception;

use RuntimeException;

class MissingDependencyException extends RuntimeException implements ExceptionInterface
{
    public static function forMiddlewareService(string $service) : self
    {
        return new self(sprintf(
            'Cannot fetch middleware service "%s"; service not registered,'
            . ' or does not resolve to an autoloadable class name',
            $service
        ));
    }
}
