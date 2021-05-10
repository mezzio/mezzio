<?php

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\WhoopsErrorResponseGeneratorFactory;
use Mezzio\Middleware\WhoopsErrorResponseGenerator;
use MezzioTest\InMemoryContainer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Whoops\Run;
use Whoops\RunInterface;

use function interface_exists;

class WhoopsErrorResponseGeneratorFactoryTest extends TestCase
{
    /** @var InMemoryContainer */
    private $container;

    /** @var Run|RunInterface&MockObject */
    private $whoops;

    public function setUp() : void
    {
        $this->container = new InMemoryContainer();

        $this->whoops = interface_exists(RunInterface::class)
            ? $this->createMock(RunInterface::class)
            : $this->createMock(Run::class);
    }

    public function testCreatesInstanceWithConfiguredWhoopsService() : void
    {
        $this->container->set('Mezzio\Whoops', $this->whoops);

        $factory = new WhoopsErrorResponseGeneratorFactory();

        $generator = $factory($this->container);

        self::assertEquals(new WhoopsErrorResponseGenerator($this->whoops), $generator);
    }
}
