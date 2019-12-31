<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio;

use Mezzio\Container\ApplicationConfigInjectionDelegator;
use SplPriorityQueue;

trait ApplicationConfigInjectionTrait
{
    /**
     * Inject a middleware pipeline from the middleware_pipeline configuration.
     *
     * Proxies to ApplicationConfigInjectionDelegator::injectPipelineFromConfig
     *
     * @param null|array $config If null, attempts to pull the 'config' service
     *     from the composed container.
     * @return void
     */
    public function injectPipelineFromConfig(array $config = null)
    {
        if (! is_array($config)
            && (! $this->container || ! $this->container->has('config'))
        ) {
            return;
        }

        ApplicationConfigInjectionDelegator::injectPipelineFromConfig(
            $this,
            is_array($config) ? $config : $this->container->get('config')
        );
    }

    /**
     * Inject routes from configuration.
     *
     * Proxies to ApplicationConfigInjectionDelegator::injectRoutesFromConfig
     *
     * @param null|array $config If null, attempts to pull the 'config' service
     *     from the composed container.
     * @return void
     * @throws Exception\InvalidArgumentException
     */
    public function injectRoutesFromConfig(array $config = null)
    {
        if (! is_array($config)
            && (! $this->container || ! $this->container->has('config'))
        ) {
            return;
        }

        ApplicationConfigInjectionDelegator::injectRoutesFromConfig(
            $this,
            is_array($config) ? $config : $this->container->get('config')
        );
    }
}
