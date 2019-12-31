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

/**
 * @deprecated since 2.2.0; to be removed in 3.0.0. Use NotFoundHandlerFactory
 *     in version 3, as it will return its replacement, the
 *     Mezzio\Handler\NotFoundHandler.
 */
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
        $layout = isset($config['mezzio']['error_handler']['layout'])
            ? $config['mezzio']['error_handler']['layout']
            : NotFoundDelegate::LAYOUT_DEFAULT;

        return new NotFoundDelegate(new Response(), $renderer, $template, $layout);
    }
}
