<?php

declare(strict_types=1);

namespace MezzioTest;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function array_key_exists;

final class InMemoryContainerV2 implements MutableMemoryContainerInterface
{
    /** @var array<string,mixed> */
    private $services = [];

    /** @return mixed */
    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new class ($id . ' was not found') extends RuntimeException implements NotFoundExceptionInterface {
            };
        }

        return $this->services[$id];
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }

    /** @param mixed $item */
    public function set(string $id, $item): void
    {
        $this->services[$id] = $item;
    }

    public function reset(): void
    {
        $this->services = [];
    }
}
