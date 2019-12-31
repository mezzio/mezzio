<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Middleware;

use Laminas\Diactoros\Response;
use Laminas\Diactoros\Stream;
use Mezzio\Router\Middleware\ImplicitHeadMiddleware as BaseImplicitHeadMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * Handle implicit HEAD requests.
 *
 * This is an extension to the canonical version provided in
 * mezzio-router v2.4 and up, and is deprecated in favor of that
 * version starting in mezzio 2.2.
 *
 * @deprecated since 2.2.0; to be removed in 3.0.0. Please use the version
 *     provided in mezzio-router 2.4+, and use the factory from
 *     that component to create an instance.
 */
class ImplicitHeadMiddleware extends BaseImplicitHeadMiddleware
{
    /**
     * @param null|ResponseInterface $response Response prototype to return
     *     for implicit HEAD requests; if none provided, an empty laminas-diactoros
     *     instance will be created.
     */
    public function __construct(ResponseInterface $response = null)
    {
        trigger_error(sprintf(
            '%s is deprecated starting with mezzio 2.2.0; please use the %s class'
            . ' provided in mezzio-router 2.4.0 and later. That class has required'
            . ' dependencies, so please also add Mezzio\Router\ConfigProvider to'
            . ' your config/config.php file as well.',
            __CLASS__,
            BaseImplicitHeadMiddleware::class
        ), E_USER_DEPRECATED);

        parent::__construct($response ?: new Response(), function () {
            return new Stream('php://temp/', 'wb+');
        });
    }
}
