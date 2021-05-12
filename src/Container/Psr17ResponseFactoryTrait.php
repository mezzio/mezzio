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
        return new CallableResponseFactoryDecorator($responseFactory);
    }
}
