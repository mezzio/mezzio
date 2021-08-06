<?php

declare(strict_types=1);

namespace Mezzio\Container;

use Mezzio\Response\CallableResponseFactoryDecorator;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Webmozart\Assert\Assert;

/**
 * @internal
 * @deprecated Will be removed in v4.0.0
 */
trait Psr17ResponseFactoryTrait
{
    private function detectResponseFactory(ContainerInterface $container): ResponseFactoryInterface
    {
        $psr17FactoryAvailable = $container->has(ResponseFactoryInterface::class);

        if (! $psr17FactoryAvailable) {
            return $this->createResponseFactoryFromDeprecatedCallable($container);
        }

        if ($this->doesConfigurationProvidesDedicatedResponseFactory($container)) {
            return $this->createResponseFactoryFromDeprecatedCallable($container);
        }

        $responseFactory = $container->get(ResponseFactoryInterface::class);
        Assert::isInstanceOf($responseFactory, ResponseFactoryInterface::class);
        return $responseFactory;
    }

    private function createResponseFactoryFromDeprecatedCallable(
        ContainerInterface $container
    ): ResponseFactoryInterface {
        /** @var callable():ResponseInterface $responseFactory */
        $responseFactory = $container->get(ResponseInterface::class);

        return new CallableResponseFactoryDecorator($responseFactory);
    }

    private function doesConfigurationProvidesDedicatedResponseFactory(ContainerInterface $container): bool
    {
        if (! $container->has('config')) {
            return false;
        }

        $config = $container->get('config');
        Assert::isArrayAccessible($config);
        $dependencies = $config['dependencies'] ?? [];
        Assert::isMap($dependencies);

        $delegators = $dependencies['delegators'] ?? [];
        $aliases    = $dependencies['aliases'] ?? [];
        Assert::isArrayAccessible($delegators);
        Assert::isArrayAccessible($aliases);

        if (isset($delegators[ResponseInterface::class]) || isset($aliases[ResponseInterface::class])) {
            // Even tho, aliases could point to a different service, we assume that there is a dedicated factory
            // available. The alias resolving is not worth it.
            return true;
        }

        /** @psalm-suppress MixedAssignment */
        $deprecatedResponseFactory = $dependencies['factories'][ResponseInterface::class] ?? null;

        return $deprecatedResponseFactory !== null && $deprecatedResponseFactory !== ResponseFactoryFactory::class;
    }
}
