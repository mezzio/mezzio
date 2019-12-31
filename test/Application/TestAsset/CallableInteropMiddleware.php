<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Application\TestAsset;

use Interop\Http\Server\RequestHandlerInterface;

class CallableInteropMiddleware
{
    public function __invoke($request, RequestHandlerInterface $handler)
    {
    }
}
