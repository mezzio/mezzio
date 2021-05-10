<?php

declare(strict_types=1);

namespace Mezzio\Container\Exception;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

/**
 * Exception indicating a service type is invalid or un-fetchable.
 */
class InvalidServiceException extends RuntimeException implements
    ContainerExceptionInterface,
    ExceptionInterface
{
}
