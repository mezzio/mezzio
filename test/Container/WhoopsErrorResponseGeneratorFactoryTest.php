<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\WhoopsErrorResponseGeneratorFactory;
use Mezzio\Middleware\WhoopsErrorResponseGenerator;
use MezzioTest\InMemoryContainerTrait;
use MezzioTest\MutableMemoryContainerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Whoops\RunInterface;

class WhoopsErrorResponseGeneratorFactoryTest extends TestCase
{
    use InMemoryContainerTrait;

    /** @var MutableMemoryContainerInterface */
    private $container;

    /** @var RunInterface&MockObject */
    private $whoops;

    public function setUp(): void
    {
        $this->container = $this->createContainer();

        $this->whoops = $this->createMock(RunInterface::class);
    }

    public function testCreatesInstanceWithConfiguredWhoopsService(): void
    {
        $this->container->set('Mezzio\Whoops', $this->whoops);

        $factory = new WhoopsErrorResponseGeneratorFactory();

        $generator = $factory($this->container);

        self::assertEquals(new WhoopsErrorResponseGenerator($this->whoops), $generator);
    }
}
