<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest\Container;

use Mezzio\Container\Exception\InvalidServiceException;
use Mezzio\Container\StreamFactoryFactory;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Throwable;

use function class_exists;
use function get_class;
use function is_array;
use function is_object;
use function preg_match;
use function spl_autoload_functions;
use function spl_autoload_register;
use function spl_autoload_unregister;

class StreamFactoryFactoryWithoutDiactorosTest extends TestCase
{
    private $autoloadFunctions = [];

    public function setUp()
    {
        class_exists(InvalidServiceException::class);

        $this->container = $this->prophesize(ContainerInterface::class)->reveal();
        $this->factory = new StreamFactoryFactory();

        foreach (spl_autoload_functions() as $autoloader) {
            if (! is_array($autoloader)) {
                continue;
            }

            $context = $autoloader[0];

            if (! is_object($context)
                || ! preg_match('/^Composer.*?ClassLoader$/', get_class($context))
            ) {
                continue;
            }

            $this->autoloadFunctions[] = $autoloader;

            spl_autoload_unregister($autoloader);
        }
    }

    public function tearDown()
    {
        $this->reloadAutoloaders();
    }

    public function reloadAutoloaders()
    {
        foreach ($this->autoloadFunctions as $autoloader) {
            spl_autoload_register($autoloader);
        }
        $this->autoloadFunctions = [];
    }

    public function testFactoryRaisesAnExceptionIfDiactorosIsNotLoaded()
    {
        $e = null;

        try {
            ($this->factory)($this->container);
        } catch (Throwable $e) {
        }

        $this->reloadAutoloaders();

        $this->assertInstanceOf(InvalidServiceException::class, $e);
        $this->assertContains('laminas/laminas-diactoros', $e->getMessage());
    }
}
