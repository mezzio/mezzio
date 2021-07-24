<?php

declare(strict_types=1);

namespace MezzioTest;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function array_key_exists;

final class InMemoryContainer implements ContainerInterface
{
    /** @var array<string,mixed> */
    private $services = [];

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
     * @return bool
     */
    public function has($id)
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
