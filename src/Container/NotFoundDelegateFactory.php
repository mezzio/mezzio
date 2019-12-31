<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

namespace Mezzio\Container;

use Laminas\Diactoros\Response;
use Mezzio\Delegate\NotFoundDelegate;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class NotFoundDelegateFactory
{
    /**
     * @param ContainerInterface $container
     * @return NotFoundDelegate
     */
    public function __invoke(ContainerInterface $container)
    {
        $config   = $container->has('config') ? $container->get('config') : [];
        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);
        $template = isset($config['mezzio']['error_handler']['template_404'])
            ? $config['mezzio']['error_handler']['template_404']
            : NotFoundDelegate::TEMPLATE_DEFAULT;

        return new NotFoundDelegate(new Response(), $renderer, $template);
    }
}
