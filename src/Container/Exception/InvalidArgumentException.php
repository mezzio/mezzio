<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container\Exception;

use Interop\Container\Exception\ContainerException;

/**
 * @deprecated since 1.1.0; to remove in 2.0.0. This exception is currently
 *     thrown by `Mezzio\Container\ApplicationFactory`; starting
 *     in 2.0.0, that factory will instead throw
 *     `Mezzio\Exception\InvalidArgumentException`.
 */
class InvalidArgumentException extends \InvalidArgumentException implements
    ContainerException,
    ExceptionInterface
{
}
