<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Handler\NotFoundHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

use function array_key_exists;

class NotFoundHandlerFactory
{
    public function __invoke(ContainerInterface $container) : NotFoundHandler
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isMap($config);

        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);

        $errorHandlerConfig = $config['mezzio']['error_handler'] ?? [];

        $template = $errorHandlerConfig['template_404'] ?? NotFoundHandler::TEMPLATE_DEFAULT;
        $layout   = array_key_exists('layout', $errorHandlerConfig)
            ? (string) $errorHandlerConfig['layout']
            : NotFoundHandler::LAYOUT_DEFAULT;

        return new NotFoundHandler(
            $container->get(ResponseInterface::class),
            $renderer,
            $template,
            $layout
        );
    }
}
