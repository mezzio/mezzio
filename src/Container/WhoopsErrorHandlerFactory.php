<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Interop\Container\ContainerInterface;
use Mezzio\Template\TemplateRendererInterface;
use Mezzio\WhoopsErrorHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Run as Whoops;

/**
 * Create and return an instance of the whoops error handler.
 *
 * Register this factory as the service `Mezzio\FinalHandler` in
 * the container of your choice.
 *
 * This factory has optional dependencies on the following services:
 *
 * - 'Mezzio\Template\TemplateRendererInterface', which should return an
 *   implementation of that interface. If not present, the error handler
 *   will not create templated responses.
 * - 'config' (which should return an array or array-like object with a
 *   "mezzio" top-level key, and an "error_handler" subkey,
 *   containing the configuration for the error handler).
 *
 * This factory has required dependencies on the following services:
 *
 * - Mezzio\Whoops, which should return a Whoops\Run instance.
 * - Mezzio\WhoopsPageHandler, which should return a
 *   Whoops\Handler\PrettyPageHandler instance.
 *
 * Configuration should look like the following:
 *
 * <code>
 * 'mezzio' => [
 *     'error_handler' => [
 *         'template_404'   => 'name of 404 template',
 *         'template_error' => 'name of error template',
 *     ],
 * ]
 * </code>
 *
 * If any of the keys are missing, default values will be used.
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
 *
 * @deprecated since 1.1.0, to be removed in 2.0.0. The "final handler" concept
 *     will be replaced with a "default delegate", which will be an
 *     implementation of Interop\Http\ServerMiddleware\DelegateInterface that
 *     returns a canned response. Mezzio will provide tools to migrate your
 *     code to use default delegates for 2.0; you will only need to manually
 *     change your code if you are extending this class.
 */
class WhoopsErrorHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $template = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);

        $config = $container->has('config')
            ? $container->get('config')
            : [];

        $mezzioConfig = isset($config['mezzio']['error_handler'])
            ? $config['mezzio']['error_handler']
            : [];

        $whoopsConfig = isset($config['whoops'])
            ? $config['whoops']
            : [];

        $whoops = $container->get('Mezzio\Whoops');
        $whoops->pushHandler($container->get('Mezzio\WhoopsPageHandler'));
        $this->registerJsonHandler($whoops, $whoopsConfig);

        return new WhoopsErrorHandler(
            $whoops,
            null,
            $template,
            (isset($mezzioConfig['template_404']) ? $mezzioConfig['template_404'] : 'error/404'),
            (isset($mezzioConfig['template_error']) ? $mezzioConfig['template_error'] : 'error/error')
        );
    }

    /**
     * If configuration indicates a JsonResponseHandler, configure and register it.
     *
     * @param Whoops $whoops
     * @param array|\ArrayAccess $config
     */
    private function registerJsonHandler(Whoops $whoops, $config)
    {
        if (! isset($config['json_exceptions']['display'])
            || empty($config['json_exceptions']['display'])
        ) {
            return;
        }

        $handler = new JsonResponseHandler();

        if (isset($config['json_exceptions']['ajax_only'])) {
            if (method_exists(\Whoops\Util\Misc::class, 'isAjaxRequest')) {
                // Whoops 2.x
                if (! \Whoops\Util\Misc::isAjaxRequest()) {
                    return;
                }
            } elseif (method_exists($handler, 'onlyForAjaxRequests')) {
                // Whoops 1.x
                $handler->onlyForAjaxRequests(true);
            }
        }

        if (isset($config['json_exceptions']['show_trace'])) {
            $handler->addTraceToOutput(true);
        }

        $whoops->pushHandler($handler);
    }
}
