<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\Diactoros\Response;
use Mezzio\Container\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ResponseFactoryTest extends TestCase
{
    public function testFactoryProducesAResponseWhenDiactorosIsInstalled()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new ResponseFactory();

        $response = $factory($container);

        $this->assertInstanceOf(Response::class, $response);
    }
}
