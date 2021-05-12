<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Response\ResponseFactory;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

class ServerRequestErrorResponseGeneratorFactory
{
    public function __invoke(ContainerInterface $container) : ServerRequestErrorResponseGenerator
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

        $dependencies = $config['dependencies'] ?? [];
        Assert::isMap($dependencies);

        $responseFactory = $this->detectResponseFactory($container, $dependencies);

        return new ServerRequestErrorResponseGenerator(
            $responseFactory,
            $debug,
            $renderer,
            $template
        );
    }

    /**
     * @param array<string,mixed> $dependencies
     */
    private function detectResponseFactory(ContainerInterface $container, array $dependencies): ResponseFactoryInterface
    {
        $psr17FactoryAvailable = $container->has(ResponseFactoryInterface::class);
        /** @psalm-suppress MixedAssignment */
        $deprecatedResponseFactory = $dependencies['aliases'][ResponseInterface::class]
            ?? $dependencies['factories'][ResponseInterface::class]
            ?? null;

        if ($psr17FactoryAvailable && $deprecatedResponseFactory === ResponseFactoryFactory::class) {
            $responseFactory = $container->get(ResponseFactoryInterface::class);
            Assert::isInstanceOf($responseFactory, ResponseFactoryInterface::class);
            return $responseFactory;
        }

        /** @var callable():ResponseInterface $responseFactory */
        $responseFactory = $container->get(ResponseInterface::class);
        return new ResponseFactory($responseFactory);
    }
}
