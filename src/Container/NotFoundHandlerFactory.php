<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Handler\NotFoundHandler;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Webmozart\Assert\Assert;

use function array_key_exists;

class NotFoundHandlerFactory
{
    public function __invoke(ContainerInterface $container) : NotFoundHandler
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isArrayAccessible($config);

        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class) : null;

        $mezzioConfiguration = $config['mezzio'] ?? [];
        Assert::isMap($mezzioConfiguration);
        $errorHandlerConfig = $mezzioConfiguration['error_handler'] ?? [];

        $template = $errorHandlerConfig['template_404'] ?? NotFoundHandler::TEMPLATE_DEFAULT;
        $layout   = array_key_exists('layout', $errorHandlerConfig)
            ? (string) $errorHandlerConfig['layout']
            : NotFoundHandler::LAYOUT_DEFAULT;

        $dependencies = $config['dependencies'] ?? [];
        Assert::isMap($dependencies);

        $responseFactory = $container->get(ResponseFactoryInterface::class);

        return new NotFoundHandler(
            $responseFactory,
            $renderer,
            $template,
            $layout
        );
    }
}
