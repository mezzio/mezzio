<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container\Template;

use Interop\Container\ContainerInterface;
use Laminas\View\HelperPluginManager;
use Laminas\View\Renderer\PhpRenderer;
use Laminas\View\Resolver;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\LaminasView;
use Mezzio\Template\LaminasViewRenderer;

/**
 * Create and return a LaminasView template instance.
 *
 * Requires the Mezzio\Router\RouterInterface service (for creating
 * the UrlHelper instance).
 *
 * Optionally requires the Laminas\View\HelperPluginManager service; if present,
 * will use the service to inject the PhpRenderer instance.
 *
 * Optionally uses the service 'config', which should return an array. This
 * factory consumes the following structure:
 *
 * <code>
 * 'templates' => [
 *     'layout' => 'name of layout view to use, if any',
 *     'map'    => [
 *         // template => filename pairs
 *     ],
 *     'paths'  => [
 *         // namespace / path pairs
 *         //
 *         // Numeric namespaces imply the default/main namespace. Paths may be
 *         // strings or arrays of string paths to associate with the namespace.
 *     ],
 * ]
 * </code>
 *
 * Injects the HelperPluginManager used by the PhpRenderer with mezzio
 * overrides of the url and serverurl helpers.
 */
class LaminasViewRendererFactory
{
    /**
     * @param ContainerInterface $container
     * @returns LaminasViewRenderer
     */
    public function __invoke(ContainerInterface $container)
    {
        $config   = $container->has('config') ? $container->get('config') : [];
        $config   = isset($config['templates']) ? $config['templates'] : [];

        // Configuration
        $resolver = new Resolver\AggregateResolver();
        $resolver->attach(
            new Resolver\TemplateMapResolver(isset($config['map']) ? $config['map'] : []),
            100
        );

        // Create the renderer
        $renderer = new PhpRenderer();
        $renderer->setResolver($resolver);

        // Inject helpers
        $this->injectHelpers($renderer, $container);

        // Inject renderer
        $view = new LaminasViewRenderer($renderer, isset($config['layout']) ? $config['layout'] : null);

        // Add template paths
        $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
        foreach ($allPaths as $namespace => $paths) {
            $namespace = is_numeric($namespace) ? null : $namespace;
            foreach ((array) $paths as $path) {
                $view->addPath($path, $namespace);
            }
        }

        return $view;
    }

    /**
     * Inject helpers into the PhpRenderer instance.
     *
     * If a HelperPluginManager instance is present in the container, uses that;
     * otherwise, instantiates one.
     *
     * In each case, injects with the custom url/serverurl implementations.
     *
     * @param PhpRenderer $renderer
     * @param ContainerInterface $container
     */
    private function injectHelpers(PhpRenderer $renderer, ContainerInterface $container)
    {
        $helpers = $container->has(HelperPluginManager::class)
            ? $container->get(HelperPluginManager::class)
            : ($container->has(\Zend\View\HelperPluginManager::class)
                ? $container->get(\Zend\View\HelperPluginManager::class)
                : new HelperPluginManager());

        $helpers->setFactory('url', function () use ($container) {
            return new LaminasView\UrlHelper($container->get(RouterInterface::class));
        });
        $helpers->setInvokableClass('serverurl', LaminasView\ServerUrlHelper::class);

        $renderer->setHelperPluginManager($helpers);
    }
}
