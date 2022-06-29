<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Diactoros\ServerRequestFilter\FilterUsingXForwardedHeaders;
use Mezzio\ConfigProvider;
use Psr\Container\ContainerInterface;

use function is_array;

/**
 * Factory for use in generating a custom FilterUsingXForwardedHeaders instance.
 *
 * Assign this factory to the * Laminas\Diactoros\ServerRequestFilter\FilterServerRequestInterface service.
 * Then define configuration as follows:
 *
 * <code>
 * 'laminas-diactoros' => [
 *     'server-request-filter' => [
 *         'x-forwarded-headers' => [
 *             // Trust any proxy:
 *             'trusted-proxies' => ['*'],
 *             // Trust specific proxies:
 *             'trusted-proxies' => ['192.168.0.1', '192.168.0.2'],
 *             // Trust entire subnets:
 *             'trusted-proxies' => ['192.168.0.0/24', '10.0.0.0/16'],
 *             // Trust specific X-Forwared headers:
 *             'trusted-headers' => ['X-Forwarded-Host', 'X-Forwarded-Proto'],
 *         ],
 *     ],
 * ],
 * </code>
*/
final class FilterUsingXForwardedHeadersFactory
{
    public function __invoke(ContainerInterface $container): FilterUsingXForwardedHeaders
    {
        // phpcs:disable Generic.Files.LineLength.TooLong
        $config = $container->get('config');
        $config = $config[ConfigProvider::DIACTOROS_CONFIG_KEY][ConfigProvider::DIACTOROS_SERVER_REQUEST_FILTER_CONFIG_KEY][ConfigProvider::DIACTOROS_X_FORWARDED_FILTER_CONFIG_KEY] ?? [];

        if (! is_array($config) || empty($config)) {
            // Trust nothing!
            return FilterUsingXForwardedHeaders::trustProxies([], []);
        }

        $proxies = $config[ConfigProvider::DIACTOROS_TRUSTED_PROXIES_CONFIG_KEY] ?? [];

        if (! is_array($proxies)) {
            // Invalid or missing configuration
            throw Exception\InvalidTrustedProxyConfigurationException::forProxies($proxies);
        }

        // Missing trusted headers setting means all headers are considered trusted
        $headers = $config[ConfigProvider::DIACTOROS_TRUSTED_HEADERS_CONFIG_KEY] ?? null;

        if (null === $headers) {
            // None specified; trust all headers for these proxies
            return FilterUsingXForwardedHeaders::trustProxies($proxies);
        }

        if (! is_array($headers)) {
            // Invalid value; trust nothing
            throw Exception\InvalidTrustedHeaderConfigurationException::forHeaders($headers);
        }

        return FilterUsingXForwardedHeaders::trustProxies($proxies, $headers);
        // phpcs:enable Generic.Files.LineLength.TooLong
    }
}
