<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Middleware;

use Interop\Http\Server\MiddlewareInterface;
use Interop\Http\Server\RequestHandlerInterface;
use Mezzio\Delegate\NotFoundDelegate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class NotFoundHandler implements MiddlewareInterface
{
    /**
     * @var NotFoundDelegate
     */
    private $internalDelegate;

    /**
     * @param NotFoundDelegate $internalDelegate
     */
    public function __construct(NotFoundDelegate $internalDelegate)
    {
        $this->internalDelegate = $internalDelegate;
    }

    /**
     * Creates and returns a 404 response.
     *
     * @param ServerRequestInterface $request Passed to internal delegate
     * @param RequestHandlerInterface $handler Ignored.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        return $this->internalDelegate->handle($request);
    }
}
