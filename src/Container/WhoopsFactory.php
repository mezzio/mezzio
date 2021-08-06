<?php

declare(strict_types=1);

namespace Mezzio\Container;

use ArrayAccess;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run as Whoops;
use Whoops\Util\Misc as WhoopsUtil;

/**
 * Create and return an instance of the Whoops runner.
 *
 * Register this factory as the service `Mezzio\Whoops` in the
 * container of your choice. This service depends on two others:
 *
 * - 'config' (which should return an array or array-like object with a "whoops"
 *   key, containing the configuration for whoops).
 * - 'Mezzio\WhoopsPageHandler', which should return a
 *   Whoops\Handler\PrettyPageHandler instance to register on the whoops
 *   instance.
 *
 * The whoops configuration can contain:
 *
 * <code>
 * 'whoops' => [
 *     'json_exceptions' => [
 *         'display'    => true,
 *         'show_trace' => true,
 *         'ajax_only'  => true,
 *     ]
 * ]
 * </code>
 *
 * All values are booleans; omission of any implies boolean false.
 */
class WhoopsFactory
{
    /**
     * Create and return an instance of the Whoops runner.
     */
    public function __invoke(ContainerInterface $container): Whoops
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isArrayAccessible($config);

        $config = $config['whoops'] ?? [];

        $whoops = new Whoops();
        $whoops->allowQuit(false);
        $whoops->pushHandler($container->get('Mezzio\WhoopsPageHandler'));
        $this->registerJsonHandler($whoops, $config);
        $whoops->register();
        return $whoops;
    }

    /**
     * If configuration indicates a JsonResponseHandler, configure and register it.
     *
     * @param array|ArrayAccess $config
     */
    private function registerJsonHandler(Whoops $whoops, $config): void
    {
        if (empty($config['json_exceptions']['display'])) {
            return;
        }

        $handler = new JsonResponseHandler();

        if (! empty($config['json_exceptions']['show_trace'])) {
            $handler->addTraceToOutput(true);
        }

        if (! empty($config['json_exceptions']['ajax_only'])) {
            // Don't push handler on stack unless we are in a XHR request.
            if (! WhoopsUtil::isAjaxRequest()) {
                return;
            }
        }

        $whoops->pushHandler($handler);
    }
}
