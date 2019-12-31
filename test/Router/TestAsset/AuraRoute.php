<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace MezzioTest\Router\TestAsset;

/**
 * Mock/stub/spy to use as a substitute for Aura.Route.
 *
 * Used for match results.
 */
class AuraRoute
{
    public $name;
    public $method;
    public $params;

    public function failedMethod()
    {
        return (null !== $this->method);
    }
}
