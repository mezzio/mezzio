<?php

declare(strict_types=1);

namespace MezzioTest;

use Psr\Container\ContainerInterface;

interface MutableMemoryContainerInterface extends ContainerInterface
{
    /** @param mixed $item */
    public function set(string $id, $item): void;

    public function reset(): void;
}
