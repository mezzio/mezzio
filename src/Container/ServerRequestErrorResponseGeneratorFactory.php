<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class ServerRequestErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : ServerRequestErrorResponseGenerator
    {
        $config = $container->has('config') ? $container->get('config') : [];
        Assert::isMap($config);

        $debug = $config['debug'] ?? false;

        $renderer = $container->has(TemplateRendererInterface::class)
            ? $container->get(TemplateRendererInterface::class)
            : ($container->has(\Zend\Expressive\Template\TemplateRendererInterface::class)
                ? $container->get(\Zend\Expressive\Template\TemplateRendererInterface::class)
                : null);

        $template = $config['mezzio']['error_handler']['template_error']
            ?? ServerRequestErrorResponseGenerator::TEMPLATE_DEFAULT;

        return new ServerRequestErrorResponseGenerator(
            $container->get(ResponseInterface::class),
            $debug,
            $renderer,
            $template
        );
    }
}
