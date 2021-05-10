<?php

declare(strict_types=1);

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
