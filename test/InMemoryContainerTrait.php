<?php

declare(strict_types=1);

namespace MezzioTest;

use Psr\Container\ContainerInterface;
use ReflectionClass;

trait InMemoryContainerTrait
{
    private ?int $containerVersion = null;

    public function createContainer(): MutableMemoryContainerInterface
    {
        if (null === $this->containerVersion) {
            $r                      = new ReflectionClass(ContainerInterface::class);
            $get                    = $r->getMethod('get');
            $this->containerVersion = $get->hasReturnType() ? 1 : 2;
        }

        return $this->containerVersion === 1
            ? new InMemoryContainer()
            : new InMemoryContainerV2();
    }
}
