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
configuration. Configuration uses the following structure:

```php
[
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
]
```

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

Alternately, you can use a configuration file to define the container. As
before, we'll define our configuration in `config/config.php`, and our
`config/container.php` file will still return our service manager instance; we'll
define the service configuration in `config/dependencies.php`:

```php
return [
    'services' => [
        'config' => include __DIR__ . '/config.php',
    ],
    'aliases' => [
        'Mezzio\Delegate\DefaultDelegate' => 'Mezzio\Delegate\NotFoundDelegate',
    ],
    'invokables' => [
        Mezzio\Router\RouterInterface::class     => Mezzio\Router\AuraRouter::class,
        Mezzio\Template\TemplateRendererInterface::class => 'Mezzio\Plates\PlatesRenderer::class
    ],
    'factories' => [
        Mezzio\Application::class       => Mezzio\Container\ApplicationFactory::class,
        'Mezzio\Whoops'            => Mezzio\Container\WhoopsFactory::class,
        'Mezzio\WhoopsPageHandler' => Mezzio\Container\WhoopsPageHandlerFactory::class,

        Laminas\Stratigility\Middleware\ErrorHandler::class    => Mezzio\Container\ErrorHandlerFactory::class,
        Mezzio\Delegate\NotFoundDelegate::class  => Mezzio\Container\NotFoundDelegateFactory::class,
        Mezzio\Middleware\NotFoundHandler::class => Mezzio\Container\NotFoundHandlerFactory::class,
    ],
];
```

`config/container.php` becomes:

```php
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;

return new ServiceManager(new Config(include 'config/dependencies.php'));
```

There is one problem, however: you may want to vary error handling strategies
based on whether or not you're in production: You have two choices on how to
approach this:

- Selectively inject the factory in the bootstrap.
- Define the final handler service in an environment specific file and use file
  globbing to merge files.

In the first case, you would change the `config/container.php` example to look
like this:

```php
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;

$container = new ServiceManager(new Config(include 'config/container.php'));
switch ($variableOrConstantIndicatingEnvironment) {
    case 'development':
        $container->setFactory(
            Mezzio\Middleware\ErrorResponseGenerator::class,
            Mezzio\Container\WhoopsErrorResponseGeneratorFactory::class
        );
        break;
    case 'production':
    default:
        $container->setFactory(
            Mezzio\Middleware\ErrorResponseGenerator::class,
            Mezzio\Container\ErrorResponseGeneratorFactory::class
        );
}
return $container;
```

In the second case, you will need to install laminas-config:

```bash
$ composer require laminas/laminas-config
```

Then, create the directory `config/autoload/`, and create two files,
`dependencies.global.php` and `dependencies.local.php`. In your `.gitignore`,
add an entry for `config/autoload/*local.php` to ensure "local"
(environment-specific) files are excluded from the repository.

`config/dependencies.php` will look like this:

```php
use Laminas\Config\Factory as ConfigFactory;

return ConfigFactory::fromFiles(
    glob('config/autoload/dependencies.{global,local}.php', GLOB_BRACE)
);
```

`config/autoload/dependencies.global.php` will look like this:

```php
return [
    'services' => [
        'config' => include __DIR__ . '/config.php',
    ],
    'aliases' => [
        'Mezzio\Delegate\DefaultDelegate' => Mezzio\Delegate\NotFoundDelegate::class,
    ],
    'invokables' => [
        Mezzio\Router\RouterInterface::class     => Mezzio\Router\AuraRouter::class,
        Mezzio\Template\TemplateRendererInterface::class => 'Mezzio\Plates\PlatesRenderer::class
    ],
    'factories' => [
        Mezzio\Application::class       => Mezzio\Container\ApplicationFactory::class,
        'Mezzio\Whoops'            => Mezzio\Container\WhoopsFactory::class,
        'Mezzio\WhoopsPageHandler' => Mezzio\Container\WhoopsPageHandlerFactory::class,

        Mezzio\Middleware\ErrorResponseGenerator::class => Mezzio\Container\ErrorResponseGeneratorFactory::class,
        Laminas\Stratigility\Middleware\ErrorHandler::class    => Mezzio\Container\ErrorHandlerFactory::class,
        'Mezzio\Delegate\NotFoundDelegate'  => Mezzio\Container\NotFoundDelegateFactory::class,
        Mezzio\Middleware\NotFoundHandler::class => Mezzio\Container\NotFoundHandlerFactory::class,
    ],
];
```

`config/autoload/dependencies.local.php` on your development machine can look
like this:

```php
return [
    'factories' => [
        'Mezzio\Whoops'            => Mezzio\Container\WhoopsFactory::class,
        'Mezzio\WhoopsPageHandler' => Mezzio\Container\WhoopsPageHandlerFactory::class,
        Mezzio\Middleware\ErrorResponseGenerator::class => 'Mezzio\Container\WhoopsErrorResponseGeneratorFactory::class,
    ],
];
```

Using the above approach allows you to keep the bootstrap file minimal and
agnostic of environment. (Note: you can take a similar approach with
the application configuration.)
