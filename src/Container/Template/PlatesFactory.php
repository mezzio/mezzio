<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container\Template;

use Interop\Container\ContainerInterface;
use League\Plates\Engine as PlatesEngine;
use Mezzio\Template\Plates;

/**
 * Create and return a Plates template instance.
 *
 * Optionally uses the service 'config', which should return an array. This
 * factory consumes the following structure:
 *
 * <code>
 * 'templates' => [
 *     'extension' => 'file extension used by templates; defaults to html',
 *     'paths' => [
 *         // namespace / path pairs
 *         //
 *         // Numeric namespaces imply the default/main namespace. Paths may be
 *         // strings or arrays of string paths to associate with the namespace.
 *     ],
 * ]
 * </code>
 */
class PlatesFactory
{
    /**
     * @param ContainerInterface $container
     * @return Plates
     */
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $config = isset($config['templates']) ? $config['templates'] : [];

        // Create the engine instance:
        $engine = new PlatesEngine();

        // Set file extension
        if (isset($config['extension'])) {
            $engine->setFileExtension($config['extension']);
        }

        // Inject engine
        $plates = new Plates($engine);

        // Add template paths
        $allPaths = isset($config['paths']) && is_array($config['paths']) ? $config['paths'] : [];
        foreach ($allPaths as $namespace => $paths) {
            $namespace = is_numeric($namespace) ? null : $namespace;
            foreach ((array) $paths as $path) {
                $plates->addPath($path, $namespace);
            }
        }

        return $plates;
    }
}
