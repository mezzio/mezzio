<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Webmozart\Assert\Assert;

class ServerRequestErrorResponseGeneratorFactory
{
    use Psr17ResponseFactoryTrait;

    public function __invoke(ContainerInterface $container): ServerRequestErrorResponseGenerator
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isArrayAccessible($config);

        $debug = $config['debug'] ?? false;

        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);

        $mezzioConfiguration = $config['mezzio'] ?? [];
        Assert::isMap($mezzioConfiguration);
        $errorHandlerConfiguration = $mezzioConfiguration['error_handler'] ?? [];
        Assert::isMap($errorHandlerConfiguration);
        $template = $errorHandlerConfiguration['template_error']
            ?? ServerRequestErrorResponseGenerator::TEMPLATE_DEFAULT;

        $responseFactory = $this->detectResponseFactory($container);

        return new ServerRequestErrorResponseGenerator(
            $responseFactory,
            $debug,
            $renderer,
            $template
        );
    }
}
