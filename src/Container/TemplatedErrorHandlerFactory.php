<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Interop\Container\ContainerInterface;
use Mezzio\TemplatedErrorHandler;

/**
 * Create and return an instance of the templated error handler.
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
 */
class TemplatedErrorHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $template = $container->has('Mezzio\Template\TemplateRendererInterface')
            ? $container->get('Mezzio\Template\TemplateRendererInterface')
            : null;

        $config = $container->has('config')
            ? $container->get('config')
            : [];

        $config = isset($config['mezzio']['error_handler'])
            ? $config['mezzio']['error_handler']
            : [];

        return new TemplatedErrorHandler(
            $template,
            (isset($config['template_404']) ? $config['template_404'] : 'error/404'),
            (isset($config['template_error']) ? $config['template_error'] : 'error/error')
        );
    }
}
