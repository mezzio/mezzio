<?php

declare(strict_types=1);

namespace Mezzio\Container\Exception;

use Mezzio\ConfigProvider;
use Mezzio\Exception\RuntimeException;

use function get_class;
use function gettype;
use function is_object;
use function sprintf;

class InvalidTrustedHeaderConfigurationException extends RuntimeException implements ExceptionInterface
{
    /** @param mixed $headers */
    public static function forHeaders($headers): self
    {
        $type = is_object($headers) ? get_class($headers) : gettype($headers);

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
