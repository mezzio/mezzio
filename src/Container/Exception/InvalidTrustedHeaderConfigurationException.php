<?php

declare(strict_types=1);

namespace Mezzio\Container\Exception;

use Mezzio\ConfigProvider;
use Mezzio\Exception\RuntimeException;

use function get_debug_type;
use function sprintf;

/** @final */
class InvalidTrustedHeaderConfigurationException extends RuntimeException implements ExceptionInterface
{
    public static function forHeaders(mixed $headers): self
    {
        $type = get_debug_type($headers);

        return new self(sprintf(
            'Invalid %s.%s.%s.%s configuration; received %s; should be list<string>',
            ConfigProvider::DIACTOROS_CONFIG_KEY,
            ConfigProvider::DIACTOROS_SERVER_REQUEST_FILTER_CONFIG_KEY,
            ConfigProvider::DIACTOROS_X_FORWARDED_FILTER_CONFIG_KEY,
            ConfigProvider::DIACTOROS_TRUSTED_HEADERS_CONFIG_KEY,
            $type,
        ));
    }
}
