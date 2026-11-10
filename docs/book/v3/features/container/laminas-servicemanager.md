# Using laminas-servicemanager

[laminas-servicemanager](https://docs.laminas.dev//laminas-servicemanager/) is a
code-driven dependency injection container provided as a standalone component by
Laminas. It features:

- lazy-loading of invokable (constructor-less) classes.
- ability to define factories for specific classes.
- ability to define generalized factories for classes with identical
  construction patterns (aka *abstract factories*).
- ability to create lazy-loading proxies.
- ability to intercept before or after instantiation to alter the construction
  workflow (aka *delegator factories*).
- interface injection (via *initializers*).

laminas-servicemanager may either be created and populated programmatically, or via
configuration. Within Mezzio application configuration, container configuration
is provided under the top-level `dependencies` key:

```php
[
    'dependencies' => [
        'services' => [
            'service name' => $serviceInstance,
        ],
        'invokables' => [
            'service name' => 'class to instantiate',
        ],
        'factories' => [
            'service name' => 'callable, Laminas\ServiceManager\FactoryInterface instance, or name of factory class returning the service',
        ],
        'abstract_factories' => [
            'class name of Laminas\ServiceManager\AbstractFactoryInterface implementation',
        ],
        'delegators' => [
            'service name' => [
                'class name of Laminas\ServiceManager\DelegatorFactoryInterface implementation',
            ],
        ],
        'lazy_services' => [
            'class_map' => [
                'service name' => 'Class\Name\Of\Service',
            ],
        ],
        'initializers' => [
            'callable, Laminas\ServiceManager\InitializerInterface implementation, or name of initializer class',
        ],
    ],
]
```

See the [container configuration format](config.md#the-format) for more detail
about how Mezzio consumes this structure.

Read more about laminas-servicemanager in [its documentation](https://docs.laminas.dev/laminas-servicemanager/).

## Installing laminas-servicemanager

To use laminas-servicemanager with mezzio, you can install it via
composer:

```bash
$ composer require laminas/laminas-servicemanager
```

## Configuring laminas-servicemanager

You can configure laminas-servicemanager either programmatically or via
configuration. We'll show you both methods.

### Programmatically

To use laminas-servicemanager programatically, you'll need to create a
`Laminas\ServiceManager\ServiceManager` instance, and then start populating it.

For this example, we'll assume your application configuration (used by several
factories to configure instances) is in `config/config.php`, and that that file
returns an array.

We'll create a `config/container.php` file that creates and returns a
`Laminas\ServiceManager\ServiceManager` instance as follows:

```php
use Laminas\ServiceManager\ServiceManager;

$container = new ServiceManager();

// Application and configuration
$container->setService('config', include 'config/config.php');
$container->setFactory(
    Mezzio\Application::class,
    Mezzio\Container\ApplicationFactory::class
);

// Routing
// In most cases, you can instantiate the router you want to use without using a
// factory:
$container->setInvokableClass(
    Mezzio\Router\RouterInterface::class,
    Mezzio\Router\AuraRouter::class
);

// Templating
// In most cases, you can instantiate the template renderer you want to use
// without using a factory:
$container->setInvokableClass(
    Mezzio\Template\TemplateRendererInterface::class,
    Mezzio\Plates\PlatesRenderer::class
);

// These next two can be added in any environment; they won't be used unless
// you add the WhoopsErrorResponseGenerator as the ErrorResponseGenerator
// implementation:
$container->setFactory(
    'Mezzio\Whoops',
    Mezzio\Container\WhoopsFactory::class
);
$container->setFactory(
    'Mezzio\WhoopsPageHandler',
    Mezzio\Container\WhoopsPageHandlerFactory::class
);

// Error Handling

// All environments:
$container->setFactory(
    Mezzio\Middleware\ErrorHandler::class,
    Mezzio\Container\ErrorHandlerFactory::class
);

// If in development:
$container->setFactory(
    Mezzio\Middleware\ErrorResponseGenerator::class,
    Mezzio\Container\WhoopsErrorResponseGeneratorFactory::class
);

// If in production:
$container->setFactory(
    Mezzio\Middleware\ErrorResponseGenerator::class,
    Mezzio\Container\ErrorResponseGeneratorFactory::class
);

return $container;
```

Your bootstrap (typically `public/index.php`) will then look like this:

```php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$container = require 'config/container.php';
$app = $container->get(\Mezzio\Application::class);

require 'config/pipeline.php';
require 'config/routes.php';

// All versions:
$app->run();
```

### Configuration-Driven Container

Alternately, you can use application configuration to define the container.
Service definitions are commonly placed in files such as
`config/autoload/dependencies.global.php`, where they are nested under the
top-level `dependencies` key:

```php
return [
    'dependencies' => [
        'aliases' => [
            SomeInterface::class => SomeImplementation::class,
        ],
        'factories' => [
            SomeImplementation::class => SomeImplementationFactory::class,
        ],
    ],
];
```

`config/config.php` aggregates this configuration with configuration providers
from Mezzio and other packages. `config/container.php` then passes only the
`dependencies` section to laminas-servicemanager while making the complete
application configuration available as the `config` service:

```php
use Laminas\ServiceManager\ServiceManager;

$config = require __DIR__ . '/config.php';

$dependencies                       = $config['dependencies'];
$dependencies['services']['config'] = $config;

return new ServiceManager($dependencies);
```

Environment-specific configuration can use the same structure in
`config/autoload/dependencies.local.php`. For example, a development-only
factory override can look like this:

```php
return [
    'dependencies' => [
        'factories' => [
            SomeImplementation::class => DevelopmentImplementationFactory::class,
        ],
    ],
];
```

See the [configuration quick start](../../getting-started/quick-start.md#config-aggregator)
for details on aggregating global and local configuration.
