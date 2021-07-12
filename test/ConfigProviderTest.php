<?php

declare(strict_types=1);

namespace MezzioTest;

use Laminas\HttpHandlerRunner\Emitter\EmitterInterface;
use Laminas\HttpHandlerRunner\RequestHandlerRunner;
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stratigility\Middleware\ErrorHandler;
use Mezzio\Application;
use Mezzio\ApplicationPipeline;
use Mezzio\ConfigProvider;
use Mezzio\Handler\NotFoundHandler;
use Mezzio\Middleware;
use Mezzio\MiddlewareContainer;
use Mezzio\MiddlewareFactory;
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

class ConfigProviderTest extends TestCase
{
    /** @var ConfigProvider */
    private $provider;

    public function setUp() : void
    {
        $this->provider = new ConfigProvider();
    }

    public function testProviderDefinesExpectedFactoryServices() : void
    {
        $config = $this->provider->getDependencies();
        $factories = $config['factories'];

        $this->assertArrayHasKey(Application::class, $factories);
        $this->assertArrayHasKey(ApplicationPipeline::class, $factories);
        $this->assertArrayHasKey(EmitterInterface::class, $factories);
        $this->assertArrayHasKey(ErrorHandler::class, $factories);
        $this->assertArrayHasKey(MiddlewareContainer::class, $factories);
        $this->assertArrayHasKey(MiddlewareFactory::class, $factories);
        $this->assertArrayHasKey(Middleware\ErrorResponseGenerator::class, $factories);
        $this->assertArrayHasKey(NotFoundHandler::class, $factories);
        $this->assertArrayHasKey(RequestHandlerRunner::class, $factories);
        $this->assertArrayHasKey(ResponseInterface::class, $factories);
        $this->assertArrayHasKey(ServerRequestInterface::class, $factories);
        $this->assertArrayHasKey(ServerRequestErrorResponseGenerator::class, $factories);
        $this->assertArrayHasKey(StreamInterface::class, $factories);
    }

    public function testInvocationReturnsArrayWithDependencies() : void
    {
        $config = ($this->provider)();
        $this->assertIsArray($config);
        $this->assertArrayHasKey('dependencies', $config);
        $this->assertArrayHasKey('factories', $config['dependencies']);
    }

    public function testServicesDefinedInConfigProvider() : void
    {
        $config = ($this->provider)();

        $json = json_decode(
            file_get_contents(__DIR__ . '/../composer.lock'),
            true
        );
        foreach ($json['packages'] as $package) {
            if (isset($package['extra']['laminas']['config-provider'])) {
                $configProvider = new $package['extra']['laminas']['config-provider']();
                $config = array_merge_recursive($config, $configProvider());
            }
        }

        $config['dependencies']['services'][RouterInterface::class] = $this->createMock(RouterInterface::class);
        $container = $this->getContainer($config['dependencies']);

        $dependencies = $this->provider->getDependencies();
        foreach ($dependencies['factories'] as $name => $factory) {
            $this->assertTrue($container->has($name), sprintf('Container does not contain service %s', $name));
            $this->assertIsObject(
                $container->get($name),
                sprintf('Cannot get service %s from container using factory %s', $name, $factory)
            );
        }

        foreach ($dependencies['aliases'] ?? [] as $alias => $dependency) {
            $this->assertTrue(
                $container->has($alias),
                sprintf('Container does not contain service with alias %s', $alias)
            );
            $this->assertIsObject(
                $container->get($alias),
                sprintf('Cannot get service %s using alias %s', $dependency, $alias)
            );
        }
    }

    private function getContainer(array $dependencies) : ServiceManager
    {
        $container = new ServiceManager();
        (new Config($dependencies))->configureServiceManager($container);

        return $container;
    }
}
