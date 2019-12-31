<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Template;

use Mezzio\Exception;
use Traversable;

trait ArrayParametersTrait
{
    /**
     * Cast params to an array, if possible.
     *
     * @param mixed $params
     * @return array
     * @throws Exception\InvalidArgumentException for non-array, non-object parameters.
     */
    private function normalizeParams($params)
    {
        if (null === $params) {
            return [];
        }

        if (is_array($params)) {
            return $params;
        }

        if ($params instanceof Traversable) {
            return iterator_to_array($params);
        }

        if (is_object($params)) {
            return (array) $params;
        }

        throw new Exception\InvalidArgumentException(sprintf(
            '%s template adapter can only handle arrays, Traversables, and objects '
            . 'when rendering; received %s',
            get_class($this),
            gettype($params)
        ));
    }
}
