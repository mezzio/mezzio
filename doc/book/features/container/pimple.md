# Using Pimple

[Pimple](http://pimple.sensiolabs.org/) is a widely used code-driven dependency
injection container provided as a standalone component by SensioLabs. It
features:

- combined parameter and service storage.
- ability to define factories for specific classes.
- lazy-loading via factories.

Pimple only supports programmatic creation at this time.

## Installing Pimple

Pimple does not currently (as of v3) implement
[PSR-11 Container](https://github.com/php-fig/container); as
such, you need to install the `xtreamwayz/pimple-container-interop` project,
which provides a [PSR-11 Container](https://github.com/php-fig/container)
wrapper around Pimple v3:

```bash
$ composer require xtreamwayz/pimple-container-interop
```

## Configuring Pimple

To configure Pimple, instantiate it, and then add the factories desired. We
recommend doing this in a dedicated script that returns the Pimple instance; in
this example, we'll have that in `config/container.php`.

```php
use Xtreamwayz\Pimple\Container as Pimple;
use Mezzio\Container;
use Mezzio\Plates\PlatesRenderer;
use Mezzio\Router;
use Mezzio\Template\TemplateRendererInterface;

$container = new Pimple();

// Application and configuration
$container['config'] = include 'config/config.php';
$container['Mezzio\Application'] = new Container\ApplicationFactory;

// Routing
// In most cases, you can instantiate the router you want to use without using a
// factory:
$container['Mezzio\Router\RouterInterface'] = function ($container) {
    return new Router\Aura();
};

// Mezzio 2.X: We'll provide a default delegate:
$delegateFactory = new Container\NotFoundDelegateFactory();
$container['Mezzio\Delegate\DefaultDelegate'] = $delegateFactory;
$container[Mezzio\Delegate\NotFoundDelegate::class] = $delegateFactory;

// Mezzio 2.X: We'll provide a not found handler:
$container[Mezzio\Middleware\NotFoundHandler::class] = new Container\NotFoundHandlerFactory();

// Templating
// In most cases, you can instantiate the template renderer you want to use
// without using a factory:
$container[TemplateRendererInterface::class] = function ($container) {
    return new PlatesRenderer();
};

// These next two can be added in any environment; they won't be used unless:
// - (Mezzio 1.X): you add the WhoopsErrorHandler as the FinalHandler
//   implementation:
// - (Mezzio 2.X): you add the WhoopsErrorResponseGenerator as the
//   ErrorResponseGenerator implementation
$container['Mezzio\Whoops'] = new Container\WhoopsFactory();
$container['Mezzio\WhoopsPageHandler'] = new Container\WhoopsPageHandlerFactory();

// Error Handling

// - In Mezzio 2.X, all environments:
$container['Mezzio\Middleware\ErrorHandler'] = new Container\ErrorHandlerFactory();

// If in development:
// - Mezzio 1.X:
$container['Mezzio\FinalHandler'] = new Container\WhoopsErrorHandlerFactory();
// - Mezzio 2.X:
$container[Mezzio\Middleware\ErrorResponseGenerator::class] = new Container\WhoopsErrorResponseGeneratorFactory();

// If in production:
// - Mezzio 1.X:
$container['Mezzio\FinalHandler'] = new Container\TemplatedErrorHandlerFactory();
// - Mezzio 2.X:
$container[Mezzio\Middleware\ErrorResponseGenerator::class] = new Container\ErrorResponseGeneratorFactory();

return $container;
```

Your bootstrap (typically `public/index.php`) will then look like this:

```php
chdir(dirname(__DIR__));
$container = require 'config/container.php';
$app = $container->get(Mezzio\Application::class);

// In Mezzio 2.X:
require 'config/pipeline.php';
require 'config/routes.php';

// All versions:
$app->run();
```

> ### Environments
> 
> In the example above, we provide two alternate definitions for
> either the service `Mezzio\FinalHandler` (Mezzio 1.X) or the
> service `Mezzio\Middleware\ErrorResponseGenerator` (Mezzio 2.X),
> one for development and one for production. You will need to add logic to
> your file to determine which definition to provide; this could be accomplished
> via an environment variable.
