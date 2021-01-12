<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace MezzioTest;

use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;

use function array_key_exists;

final class InMemoryContainer implements ContainerInterface
{
    private $services = [];

    public function get($id)
    {
        if (! $this->has($id)) {
            throw new class($id . ' was not found') extends RuntimeException implements NotFoundExceptionInterface {
            };
        }

        return $this->services[$id];
    }

    public function has($id)
    {
        return array_key_exists($id, $this->services);
    }

    /** @param mixed $item */
    public function set(string $id, $item) : void
    {
        $this->services[$id] = $item;
    }

    public function reset() : void
    {
        $this->services = [];
    }
}
