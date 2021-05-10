<?php

declare(strict_types=1);

namespace MezzioTest\Container\TestAsset;

class CallableMiddleware
{
    public function __invoke($request, $response, callable $next)
    {
        return $response;
    }
}
