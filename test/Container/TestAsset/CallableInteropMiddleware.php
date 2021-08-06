<?php

declare(strict_types=1);

namespace MezzioTest\Container\TestAsset;

use Psr\Http\Server\RequestHandlerInterface;

class CallableInteropMiddleware
{
    public function __invoke($request, RequestHandlerInterface $handler): void
    {
    }
}
