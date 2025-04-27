<?php

declare(strict_types=1);

namespace MezzioTest\Container\TestAsset;

final class CallableMiddleware
{
    public function __invoke($request, $response, callable $next)
    {
        return $response;
    }
}
