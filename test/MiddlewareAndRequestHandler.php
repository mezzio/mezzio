<?php

declare(strict_types=1);

namespace MezzioTest;

use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

interface MiddlewareAndRequestHandler extends RequestHandlerInterface, MiddlewareInterface
{
}
