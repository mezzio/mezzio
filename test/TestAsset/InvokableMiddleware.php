<?php

declare(strict_types=1);

namespace MezzioTest\TestAsset;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class InvokableMiddleware
{
    public function __invoke(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return self::staticallyCallableMiddleware($request, $handler);
    }

    public static function staticallyCallableMiddleware(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        return $handler->handle($request)->withHeader('X-Invoked', self::class);
    }
}
