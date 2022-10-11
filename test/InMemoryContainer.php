<?php

declare(strict_types=1);

namespace MezzioTest;

use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function array_key_exists;

final class InMemoryContainer implements MutableMemoryContainerInterface
{
    /** @var array<string,mixed> */
    private array $services = [];

    /**
     * @param string $id
     * @return mixed
     */
    public function get($id)
    {
        if (! $this->has($id)) {
            throw new class ($id . ' was not found') extends RuntimeException implements NotFoundExceptionInterface {
            };
        }

        return $this->services[$id];
    }

    /**
     * @param string $id
     */
    public function has($id): bool
    {
        return array_key_exists($id, $this->services);
    }

    public function set(string $id, mixed $item): void
    {
        $this->services[$id] = $item;
    }

    public function reset(): void
    {
        $this->services = [];
    }
}
