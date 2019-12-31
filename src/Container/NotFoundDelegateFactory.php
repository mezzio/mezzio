<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Laminas\Diactoros\Response;
use Mezzio\Delegate\NotFoundDelegate;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;

class NotFoundDelegateFactory
{
    public function __invoke(ContainerInterface $container) : NotFoundDelegate
    {
        $config   = $container->has('config') ? $container->get('config') : [];
        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);
        $template = $config['mezzio']['error_handler']['template_404']
            ?? NotFoundDelegate::TEMPLATE_DEFAULT;
        $layout = $config['mezzio']['error_handler']['layout']
            ?? NotFoundDelegate::LAYOUT_DEFAULT;

        return new NotFoundDelegate(new Response(), $renderer, $template, $layout);
    }
}
