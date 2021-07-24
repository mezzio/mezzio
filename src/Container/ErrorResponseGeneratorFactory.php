<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Middleware\ErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

use function array_key_exists;

class ErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container): ErrorResponseGenerator
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isArrayAccessible($config);

        $debug               = $config['debug'] ?? false;
        $mezzioConfiguration = $config['mezzio'] ?? [];
        Assert::isMap($mezzioConfiguration);

        $errorHandlerConfig = $mezzioConfiguration['error_handler'] ?? [];

        $template = $errorHandlerConfig['template_error'] ?? ErrorResponseGenerator::TEMPLATE_DEFAULT;
        $layout   = array_key_exists('layout', $errorHandlerConfig)
            ? (string) $errorHandlerConfig['layout']
            : ErrorResponseGenerator::LAYOUT_DEFAULT;

        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);

        return new ErrorResponseGenerator($debug, $renderer, $template, $layout);
    }
}
