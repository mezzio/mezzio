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
[container-interop](https://github.com/container-interop/container-interop); as
such, you need to install the `xtreamwayz/pimple-container-interop` project,
which provides a container-interop wrapper around Pimple v3:

```bash
$ composer require xtreamwayz/pimple-container-interop
```

## Configuring Pimple

To configure Pimple, instantiate it, and then add the factories desired. We
recommend doing this in a dedicated script that returns the Pimple instance; in
this example, we'll have that in `config/services.php`.

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

// Templating
// In most cases, you can instantiate the template renderer you want to use
// without using a factory:
$container[TemplateRendererInterface::class] = function ($container) {
    return new PlatesRenderer();
};

// These next two can be added in any environment; they won't be used unless
// you add the WhoopsErrorHandler as the FinalHandler implementation:
$container['Mezzio\Whoops'] = new Container\WhoopsFactory();
$container['Mezzio\WhoopsPageHandler'] = new Container\WhoopsPageHandlerFactory();

// Error Handling
// If in development:
$container['Mezzio\FinalHandler'] = new Container\WhoopsErrorHandlerFactory();

// If in production:
$container['Mezzio\FinalHandler'] = new Container\TemplatedErrorHandlerFactory();

return $container;
```

Your bootstrap (typically `public/index.php`) will then look like this:

```php
chdir(dirname(__DIR__));
$container = require 'config/services.php';
$app = $container->get('Mezzio\Application');
$app->run();
```

> ### Environments
>
> In the example above, we provide two alternate definitions for the service
> `Mezzio\FinalHandler`, one for development and one for production.
> You will need to add logic to your file to determine which definition to
> provide; this could be accomplished via an environment variable.
