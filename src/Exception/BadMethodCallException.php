<?php

declare(strict_types=1);

namespace Mezzio\Exception;

/** @final */
class BadMethodCallException extends \BadMethodCallException implements
    ExceptionInterface
{
}
