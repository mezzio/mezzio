<?php

/**
 * @see       https://github.com/mezzio/mezzio for the canonical source repository
 * @copyright https://github.com/mezzio/mezzio/blob/master/COPYRIGHT.md
 * @license   https://github.com/mezzio/mezzio/blob/master/LICENSE.md New BSD License
 */

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Handler\NotFoundHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;

class NotFoundHandlerFactory
{
    public function __invoke(ContainerInterface $container) : NotFoundHandler
    {
        $config   = $container->has('config') ? $container->get('config') : [];
        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);
        $template = $config['mezzio']['error_handler']['template_404']
            ?? NotFoundHandler::TEMPLATE_DEFAULT;
        $layout   = $config['mezzio']['error_handler']['layout']
            ?? NotFoundHandler::LAYOUT_DEFAULT;

        return new NotFoundHandler(
            $container->get(ResponseInterface::class),
            $renderer,
            $template,
            $layout
        );
    }
}
