<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container\Exception;

use Interop\Container\Exception\ContainerException;

class InvalidArgumentException extends \InvalidArgumentException implements
    ContainerException,
    ExceptionInterface
{
}
