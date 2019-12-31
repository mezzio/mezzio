<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Template;

class TemplatePath
{
    /**
     * @var string
     */
    protected $path;

    /**
     * @var null|string
     */
    protected $namespace;

    /**
     * Constructor
     *
     * @param string $path
     * @param null|string $namespace
     */
    public function __construct($path, $namespace = null)
    {
        $this->path      = $path;
        $this->namespace = $namespace;
    }

    /**
     * Casts to string by returning the path only.
     *
     * @return string
     */
    public function __toString()
    {
        return $this->path;
    }

    /**
     * Get the namespace
     *
     * @return null|string
     */
    public function getNamespace()
    {
        return $this->namespace;
    }

    /**
     * Get the path
     *
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }
}
