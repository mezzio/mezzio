<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use Laminas\Diactoros\Response;
use Mezzio\Container\ResponseFactoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

class ResponseFactoryFactoryTest extends TestCase
{
    public function testFactoryProducesACallableCapableOfGeneratingAResponseWhenDiactorosIsInstalled(): void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory   = new ResponseFactoryFactory();

        $result = $factory($container);

        $this->assertIsCallable($result);

        $response = $result();
        $this->assertInstanceOf(Response::class, $response);
    }
}
