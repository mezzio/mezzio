<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\Diactoros\Response;
use Mezzio\Container\ResponseFactoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ResponseFactoryFactoryTest extends TestCase
{
    public function testFactoryProducesACallableCapableOfGeneratingAResponseWhenDiactorosIsInstalled() : void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new ResponseFactoryFactory();

        $result = $factory($container);

        $this->assertInternalType('callable', $result);

        $response = $result();
        $this->assertInstanceOf(Response::class, $response);
    }
}
