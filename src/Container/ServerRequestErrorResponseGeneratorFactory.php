<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Webmozart\Assert\Assert;

class ServerRequestErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : ServerRequestErrorResponseGenerator
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isArrayAccessible($config);

        $debug = $config['debug'] ?? false;

        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class) : null;

        $mezzioConfiguration = $config['mezzio'] ?? [];
        Assert::isMap($mezzioConfiguration);
        $errorHandlerConfiguration = $mezzioConfiguration['error_handler'] ?? [];
        Assert::isMap($errorHandlerConfiguration);
        $template = $errorHandlerConfiguration['template_error']
            ?? ServerRequestErrorResponseGenerator::TEMPLATE_DEFAULT;

        $dependencies = $config['dependencies'] ?? [];
        Assert::isMap($dependencies);

        $responseFactory = $container->get(ResponseFactoryInterface::class);

        return new ServerRequestErrorResponseGenerator(
            $responseFactory,
            $debug,
            $renderer,
            $template
        );
    }
}
