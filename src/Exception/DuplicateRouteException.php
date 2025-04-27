<?php

declare(strict_types=1);

namespace Mezzio\Exception;

use DomainException;

/** @final */
class DuplicateRouteException extends DomainException implements
    ExceptionInterface
{
}
