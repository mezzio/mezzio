<?php

declare(strict_types=1);

namespace MezzioTest;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\HttpHandlerRunner\RequestHandlerRunnerInterface;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ConfigInterface;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\ApplicationPipeline;
use Mezzio\ConfigProvider;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Middleware;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
use Mezzio\MiddlewareFactoryInterface;
use Mezzio\Response\ServerRequestErrorResponseGenerator;
use Mezzio\Router\RouterInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

use function array_merge_recursive;
use function file_get_contents;
use function json_decode;
use function sprintf;

use const Mezzio\DEFAULT_DELEGATE;
use const Mezzio\DISPATCH_MIDDLEWARE;
use const Mezzio\IMPLICIT_HEAD_MIDDLEWARE;
use const Mezzio\IMPLICIT_OPTIONS_MIDDLEWARE;
use const Mezzio\NOT_FOUND_MIDDLEWARE;
use const Mezzio\ROUTE_MIDDLEWARE;

/** @psalm-import-type ServiceManagerConfigurationType from ConfigInterface */
class ConfigProviderTest extends TestCase
{
    private ConfigProvider $provider;

    public function setUp(): void
    {
        $this->provider = new ConfigProvider();
    }

    public function testProviderDefinesExpectedAliases(): void
    {
        $aliases = $this->provider->getDependencies()['aliases'] ?? [];

        self::assertArrayHasKey(RequestHandlerRunnerInterface::class, $aliases);
        self::assertArrayHasKey(MiddlewareFactoryInterface::class, $aliases);
        self::assertArrayHasKey(DEFAULT_DELEGATE, $aliases);
        self::assertArrayHasKey(DISPATCH_MIDDLEWARE, $aliases);
        self::assertArrayHasKey(IMPLICIT_HEAD_MIDDLEWARE, $aliases);
        self::assertArrayHasKey(IMPLICIT_OPTIONS_MIDDLEWARE, $aliases);
        self::assertArrayHasKey(NOT_FOUND_MIDDLEWARE, $aliases);
        self::assertArrayHasKey(ROUTE_MIDDLEWARE, $aliases);
    }

    public function testProviderDefinesExpectedFactoryServices(): void
    {
        $factories = $this->provider->getDependencies()['factories'] ?? [];

        self::assertArrayHasKey(Application::class, $factories);
        self::assertArrayHasKey(ApplicationPipeline::class, $factories);
        self::assertArrayHasKey(EmitterInterface::class, $factories);
        self::assertArrayHasKey(ErrorHandler::class, $factories);
        self::assertArrayHasKey(MiddlewareContainer::class, $factories);
        self::assertArrayHasKey(MiddlewareFactory::class, $factories);
        self::assertArrayHasKey(Middleware\ErrorResponseGenerator::class, $factories);
        self::assertArrayHasKey(NotFoundHandler::class, $factories);
        self::assertArrayHasKey(RequestHandlerRunner::class, $factories);
        self::assertArrayHasKey(ResponseInterface::class, $factories);
        self::assertArrayHasKey(ServerRequestInterface::class, $factories);
        self::assertArrayHasKey(ServerRequestErrorResponseGenerator::class, $factories);
        self::assertArrayHasKey(StreamInterface::class, $factories);
    }

    public function testInvocationReturnsArrayWithDependencies(): void
    {
        $config = ($this->provider)();
        self::assertIsArray($config);
        self::assertArrayHasKey('dependencies', $config);
        self::assertArrayHasKey('aliases', $config['dependencies']);
        self::assertArrayHasKey('factories', $config['dependencies']);
    }

    public function testServicesDefinedInConfigProvider(): void
    {
        $config = ($this->provider)();

        $json = json_decode(
            file_get_contents(__DIR__ . '/../composer.lock'),
            true
        );
        foreach ($json['packages'] as $package) {
            if (isset($package['extra']['laminas']['config-provider'])) {
                $configProvider = new $package['extra']['laminas']['config-provider']();
                self::assertIsCallable($configProvider);
                $value = $configProvider();
                self::assertIsArray($value);
                $config = array_merge_recursive($config, $value);
            }
        }

        $config['dependencies']['services'][RouterInterface::class] = $this->createMock(RouterInterface::class);
        $config['dependencies']['services']['config']               = $config;
        $container                                                  = $this->getContainer($config['dependencies']);

        $dependencies = $this->provider->getDependencies();
        foreach ($dependencies['factories'] ?? [] as $name => $factory) {
            self::assertIsString($factory);
            self::assertTrue($container->has($name), sprintf('Container does not contain service %s', $name));
            self::assertIsObject(
                $container->get($name),
                sprintf('Cannot get service %s from container using factory %s', $name, $factory)
            );
        }

        foreach ($dependencies['aliases'] ?? [] as $alias => $dependency) {
            self::assertIsString($alias);
            self::assertIsString($dependency);
            self::assertTrue(
                $container->has($alias),
                sprintf('Container does not contain service with alias %s', $alias)
            );
            self::assertIsObject(
                $container->get($alias),
                sprintf('Cannot get service %s using alias %s', $dependency, $alias)
            );
        }
    }

    /** @psalm-param ServiceManagerConfigurationType $dependencies */
    private function getContainer(array $dependencies): ServiceManager
    {
        $container = new ServiceManager();
        (new Config($dependencies))->configureServiceManager($container);

        return $container;
    }
}
