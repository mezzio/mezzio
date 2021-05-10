<?php

declare(strict_types=1);

namespace Mezzio\Exception;

class BadMethodCallException extends \BadMethodCallException implements
    ExceptionInterface
{
}
