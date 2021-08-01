<?php

declare(strict_types=1);

namespace Mezzio\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

use function sprintf;

class MissingDependencyException extends RuntimeException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
    public static function forMiddlewareService(string $service): self
    {
        return new self(sprintf(
            'Cannot fetch middleware service "%s"; service not registered,'
            . ' or does not resolve to an autoloadable class name',
            $service
        ));
    }
}
