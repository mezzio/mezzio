<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\Diactoros\Stream;
use Mezzio\Container\StreamFactoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class StreamFactoryFactoryTest extends TestCase
{
    public function testFactoryProducesACallableCapableOfGeneratingAStreamWhenDiactorosIsInstalled()
    {
        $container = $this->prophesize(ContainerInterface::class)->reveal();
        $factory = new StreamFactoryFactory();

        $result = $factory($container);

        $this->assertInternalType('callable', $result);

        $stream = $result();
        $this->assertInstanceOf(Stream::class, $stream);
    }
}
