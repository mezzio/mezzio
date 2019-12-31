<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Middleware;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Mezzio\Delegate\NotFoundDelegate;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * @deprecated since 2.2.0; to be removed in 3.0.0. Version 3.0.0 will reuse
 *     re-use the Mezzio\Handler\NotFoundHandler directly within a
 *     middleware pipeline instead.
 */
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
     * @param DelegateInterface $delegate Ignored.
     * @return ResponseInterface
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        return $this->internalDelegate->process($request);
    }
}
