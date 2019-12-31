<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Template\Twig;

use Mezzio\Router\RouterInterface;
use Twig_Extension;
use Twig_SimpleFunction;

/**
 * Twig extension for rendering URLs and assets URLs from Mezzio.
 *
 * @author Geert Eltink (https://xtreamwayz.github.io)
 */
class TwigExtension extends Twig_Extension
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var string
     */
    private $assetsUrl;

    /**
     * @var string
     */
    private $assetsVersion;

    /**
     * @param RouterInterface $router
     * @param string $assetsUrl
     * @param string $assetsVersion
     */
    public function __construct(
        RouterInterface $router,
        $assetsUrl,
        $assetsVersion
    ) {
        $this->router        = $router;
        $this->assetsUrl     = $assetsUrl;
        $this->assetsVersion = $assetsVersion;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'mezzio';
    }

    /**
     * @return Twig_SimpleFunction[]
     */
    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('path', [$this, 'renderUri']),
            new Twig_SimpleFunction('asset', [$this, 'renderAssetUrl']),
        ];
    }

    /**
     * Usage: {{ path('name', parameters) }}
     *
     * @param $name
     * @param array $parameters
     * @param bool $relative
     * @return string
     */
    public function renderUri($name, $parameters = [], $relative = false)
    {
        return $this->router->generateUri($name, $parameters);
    }

    /**
     * Usage: {{ asset('path/to/asset/name.ext', version=3) }}
     *
     * @param $path
     * @param null $packageName
     * @param bool $absolute
     * @param null $version
     * @return string
     */
    public function renderAssetUrl($path, $packageName = null, $absolute = false, $version = null)
    {
        return sprintf(
            '%s%s?v=%s',
            $this->assetsUrl,
            $path,
            ($version) ? $version : $this->assetsVersion
        );
    }
}
