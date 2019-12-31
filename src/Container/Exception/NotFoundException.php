<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container\Exception;

use Interop\Container\Exception\NotFoundException as InteropNotFoundException;
use RuntimeException;

/**
 * Exception indicating a service was not found in the container.
 *
 * @deprecated since 1.1.0; to remove in 2.0.0. This exception is not thrown
 *     by any classes within Mezzio at this time.
 */
class NotFoundException extends RuntimeException implements
    ExceptionInterface,
    InteropNotFoundException
{
}
