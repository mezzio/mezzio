<?php

declare(strict_types=1);

namespace MezzioTest\TestAsset;

use Psr\Container\ContainerExceptionInterface;
use RuntimeException;

final class ContainerException extends RuntimeException implements ContainerExceptionInterface
{
}
