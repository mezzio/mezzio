<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\HttpHandlerRunner\Emitter\EmitterStack;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;

use function array_shift;
use function iterator_to_array;

class EmitterFactoryTest extends TestCase
{
    public function testFactoryProducesEmitterStackWithSapiEmitterComposed() : void
    {
        $container = $this->createMock(ContainerInterface::class);
        $factory = new EmitterFactory();

        $emitter = $factory($container);

        $this->assertInstanceOf(EmitterStack::class, $emitter);

        $emitters = iterator_to_array($emitter);
        $this->assertCount(1, $emitters);

        $emitter = array_shift($emitters);
        $this->assertInstanceOf(SapiEmitter::class, $emitter);
    }
}
