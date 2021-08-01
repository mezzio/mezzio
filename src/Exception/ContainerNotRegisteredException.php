<?php

declare(strict_types=1);

namespace Mezzio\Exception;

use RuntimeException;

use function sprintf;

class ContainerNotRegisteredException extends RuntimeException implements ExceptionInterface
{
    public static function forMiddlewareService(string $middleware): self
    {
        return new self(sprintf(
            'Cannot marshal middleware by service name "%s"; no container registered',
            $middleware
        ));
    }
}
