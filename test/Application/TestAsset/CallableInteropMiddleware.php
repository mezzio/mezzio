<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Application\TestAsset;

use Interop\Http\ServerMiddleware\DelegateInterface;

class CallableInteropMiddleware
{
    public function __invoke($request, DelegateInterface $delegate)
    {
    }
}
