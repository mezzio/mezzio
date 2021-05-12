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
    /**
     * @param array<string,mixed> $dependencies
     */
    private function detectResponseFactory(ContainerInterface $container, array $dependencies): ResponseFactoryInterface
    {
        $psr17FactoryAvailable = $container->has(ResponseFactoryInterface::class);

        if (! $psr17FactoryAvailable) {
            return $this->createResponseFactoryFromDeprecatedCallable($container);
        }

        /** @psalm-suppress MixedAssignment */
        $deprecatedResponseFactory = $dependencies['factories'][ResponseInterface::class] ?? null;

        if ($deprecatedResponseFactory !== ResponseFactoryFactory::class) {
            return $this->createResponseFactoryFromDeprecatedCallable($container);
        }

        $delegators = $dependencies['delegators'] ?? [];
        $aliases = $dependencies['aliases'] ?? [];
        Assert::isArrayAccessible($delegators);
        Assert::isArrayAccessible($aliases);
        if (isset($delegators[ResponseInterface::class]) || isset($aliases[ResponseInterface::class])) {
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
}
