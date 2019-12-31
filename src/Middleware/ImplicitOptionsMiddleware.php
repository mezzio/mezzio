<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Middleware;

use Laminas\Diactoros\Response;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware as BaseImplicitOptionsMiddleware;
use Psr\Http\Message\ResponseInterface;

/**
 * Handle implicit OPTIONS requests.
 *
 * This is an extension to the canonical version provided in
 * mezzio-router v2.4 and up, and is deprecated in favor of that
 * version starting in mezzio 2.2.
 *
 * @deprecated since 2.2.0; to be removed in 3.0.0. Please use the version
 *     provided in mezzio-router 2.4+, and use the factory from
 *     that component to create an instance.
 */
class ImplicitOptionsMiddleware extends BaseImplicitOptionsMiddleware
{
    /**
     * @param null|ResponseInterface $response Response prototype to use for
     *     implicit OPTIONS requests; if not provided a laminas-diactoros Response
     *     instance will be created and used.
     */
    public function __construct(ResponseInterface $response = null)
    {
        trigger_error(sprintf(
            '%s is deprecated starting with mezzio 2.2.0; please use the %s class'
            . ' provided in mezzio-router 2.4.0 and later. That class has required'
            . ' dependencies, so please also add Mezzio\Router\ConfigProvider to'
            . ' your config/config.php file as well.',
            __CLASS__,
            BaseImplicitOptionsMiddleware::class
        ), E_USER_DEPRECATED);

        parent::__construct($response ?: new Response());
    }
}
