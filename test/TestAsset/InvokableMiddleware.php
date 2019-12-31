<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\TestAsset;

class InvokableMiddleware
{
    public function __invoke($request, $response, $next)
    {
        return self::staticallyCallableMiddleware($request, $response, $next);
    }

    public static function staticallyCallableMiddleware($request, $response, $next)
    {
        return $response->withHeader('X-Invoked', __CLASS__);
    }
}
