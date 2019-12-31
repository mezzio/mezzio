<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Middleware;

use Mezzio\Middleware\ImplicitOptionsMiddleware;
use Mezzio\Router\Middleware\ImplicitOptionsMiddleware as BaseImplicitOptionsMiddleware;
use PHPUnit\Framework\TestCase;

class ImplicitOptionsMiddlewareTest extends TestCase
{
    public function testConstructorTriggersDeprecationNotice()
    {
        $test = (object) ['message' => false];
        set_error_handler(function ($errno, $errstr) use ($test) {
            $test->message = $errstr;
            return true;
        }, E_USER_DEPRECATED);

        $middleware = new ImplicitOptionsMiddleware();
        restore_error_handler();

        $this->assertInstanceOf(BaseImplicitOptionsMiddleware::class, $middleware);
        $this->assertInternalType('string', $test->message);
        $this->assertContains('deprecated starting with mezzio 2.2', $test->message);
    }
}
