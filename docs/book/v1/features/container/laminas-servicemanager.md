# Using laminas-servicemanager

[laminas-servicemanager](https://github.com/laminas/laminas-servicemanager) is a
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

We'll create a `config/services.php` file that creates and returns a
`Laminas\ServiceManager\ServiceManager` instance as follows:

```php
use Laminas\ServiceManager\ServiceManager;

$container = new ServiceManager();

// Application and configuration
$container->setService('config', include 'config/config.php');
$container->setFactory(
    'Mezzio\Application',
    'Mezzio\Container\ApplicationFactory'
);

// Routing
// In most cases, you can instantiate the router you want to use without using a
// factory:
$container->setInvokableClass(
    'Mezzio\Router\RouterInterface',
    'Mezzio\Router\AuraRouter'
);

// Templating
// In most cases, you can instantiate the template renderer you want to use
// without using a factory:
$container->setInvokableClass(
    'Mezzio\Template\TemplateRendererInterface',
    'Mezzio\Plates\PlatesRenderer'
);

// These next two can be added in any environment; they won't be used unless
// you add the WhoopsErrorHandler as the FinalHandler implementation:
$container->setFactory(
    'Mezzio\Whoops',
    'Mezzio\Container\WhoopsFactory'
);
$container->setFactory(
    'Mezzio\WhoopsPageHandler',
    'Mezzio\Container\WhoopsPageHandlerFactory'
);

// Error Handling
// If in development:
$container->setFactory(
    'Mezzio\FinalHandler',
    'Mezzio\Container\WhoopsErrorHandlerFactory'
);

// If in production:
$container->setFactory(
    'Mezzio\FinalHandler',
    'Mezzio\Container\TemplatedErrorHandlerFactory'
);

return $container;
```

Your bootstrap (typically `public/index.php`) will then look like this:

```php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$container = require 'config/services.php';
$app = $container->get('Mezzio\Application');
$app->run();
```

### Configuration-Driven Container

Alternately, you can use a configuration file to define the container. As
before, we'll define our configuration in `config/config.php`, and our
`config/services.php` file will still return our service manager instance; we'll
define the service configuration in `config/dependencies.php`:

```php
return [
    'services' => [
        'config' => include __DIR__ . '/config.php',
    ],
    'invokables' => [
        'Mezzio\Router\RouterInterface'     => 'Mezzio\Router\AuraRouter',
        'Mezzio\Template\TemplateRendererInterface' => 'Mezzio\Plates\PlatesRenderer'
    ],
    'factories' => [
        'Mezzio\Application'       => 'Mezzio\Container\ApplicationFactory',
        'Mezzio\Whoops'            => 'Mezzio\Container\WhoopsFactory',
        'Mezzio\WhoopsPageHandler' => 'Mezzio\Container\WhoopsPageHandlerFactory',
    ],
];
```

`config/services.php` becomes:

```php
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;

return new ServiceManager(new Config(include 'config/dependencies.php'));
```

There is one problem, however: which final handler should you configure? You
have two choices on how to approach this:

- Selectively inject the factory in the bootstrap.
- Define the final handler service in an environment specific file and use file
  globbing to merge files.

In the first case, you would change the `config/services.php` example to look
like this:

```php
use Laminas\ServiceManager\Config;
use Laminas\ServiceManager\ServiceManager;

$container = new ServiceManager(new Config(include 'config/services.php'));
switch ($variableOrConstantIndicatingEnvironment) {
    case 'development':
        $container->setFactory(
            'Mezzio\FinalHandler',
            'Mezzio\Container\WhoopsErrorHandlerFactory'
        );
        break;
    case 'production':
    default:
        $container->setFactory(
            'Mezzio\FinalHandler',
            'Mezzio\Container\TemplatedErrorHandlerFactory'
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
    'invokables' => [
        'Mezzio\Router\RouterInterface'     => 'Mezzio\Router\AuraRouter',
        'Mezzio\Template\TemplateRendererInterface' => 'Mezzio\Plates\PlatesRenderer'
    ],
    'factories' => [
        'Mezzio\Application'       => 'Mezzio\Container\ApplicationFactory',
        'Mezzio\FinalHandler'      => 'Mezzio\Container\TemplatedErrorHandlerFactory',
    ],
];
```

`config/autoload/dependencies.local.php` on your development machine can look
like this:

```php
return [
    'factories' => [
        'Mezzio\FinalHandler'      => 'Mezzio\Container\WhoopsErrorHandlerFactory',
        'Mezzio\Whoops'            => 'Mezzio\Container\WhoopsFactory',
        'Mezzio\WhoopsPageHandler' => 'Mezzio\Container\WhoopsPageHandlerFactory',
    ],
];
```

Using the above approach allows you to keep the bootstrap file minimal and
agnostic of environment. (Note: you can take a similar approach with
the application configuration.)
