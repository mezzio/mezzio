# Using Aura.Di

[Aura.Di](https://github.com/auraphp/Aura.Di/) provides a serializable dependency
injection container with the following features:

- constructor and setter injection.
- inheritance of constructor parameter and setter method values from parent
  classes.
- inheritance of setter method values from interfaces and traits.
- lazy-loaded instances, services, includes/requires, and values.
- instance factories.
- optional auto-resolution of typehinted constructor parameter values.

## Installing Aura.Di

Aura.Di only implements [container-interop](https://github.com/container-interop/container-interop)
as of version 3 (in beta at the time of writing).

```bash
$ composer require "aura/di:3.0.*@beta"
```

## Configuration

Aura.Di can help you to organize your code better with
[ContainerConfig classes](http://auraphp.com/packages/3.x/Di/config.html) and
[two step configuration](http://auraphp.com/blog/2014/04/07/two-stage-config/).
In this example, we'll put that in `config/services.php`:

```php
<?php

use Aura\Di\ContainerBuilder;

$containerBuilder = new ContainerBuilder();

// Use the builder to create and configure a container using an array of
// ContainerConfig classes. Make sure the classes can be autoloaded!
return $containerBuilder->newConfiguredInstance([
    'Application\Config\Common',
]);
```

The bare minimum `ContainerConfig` code needed to make mezzio work is:

```php
<?php

// In src/Config/Common.php:
namespace Application\Config;

use Aura\Di\Container;
use Aura\Di\ContainerConfig;
use Aura\Router\Generator;
use Aura\Router\RouteCollection;
use Aura\Router\RouteFactory;
use Aura\Router\Router;
use Laminas\Escaper\Escaper;
use Mezzio\Application;
use Mezzio\Container as MezzioContainer;
use Mezzio\Plates\PlatesRenderer;
use Mezzio\Router\AuraRouter;
use Mezzio\Router\Route;
use Mezzio\Router\RouterInterface;
use Mezzio\Template\TemplateRendererInterface;

class Common extends ContainerConfig
{
    public function define(Container $di)
    {
        $di->params[RouteCollection::class] = array(
            'route_factory' => $di->lazyNew(RouteFactory::class),
        );
        $di->params[Router::class] = array(
            'routes' => $di->lazyNew(RouteCollection::class),
            'generator' => $di->lazyNew(Generator::class),
        );
        $di->params[AuraRouter::class]['router'] = $di->lazyNew(Router::class);
        $di->set(RouterInterface::class, $di->lazyNew(AuraRouter::class));
        $di->set(MezzioContainer\ApplicationFactory::class, $di->lazyNew(MezzioContainer\ApplicationFactory::class));
        $di->set(Application::class, $di->lazyGetCall(MezzioContainer\ApplicationFactory::class, '__invoke', $di));

        // Templating
        // In most cases, you can instantiate the template renderer you want to use
        // without using a factory:
        $di->set(TemplateRendererInterface::class, $di->lazyNew(PlatesRenderer::class));

        // These next two can be added in any environment; they won't be used unless
        // you add the WhoopsErrorHandler as the FinalHandler implementation:
        $di->set(MezzioContainer\WhoopsFactory::class, $di->lazyNew(MezzioContainer\WhoopsFactory::class));
        $di->set('Mezzio\Whoops', $di->lazyGetCall(MezzioContainer\WhoopsFactory::class, '__invoke', $di));
        $di->set(MezzioContainer\WhoopsPageHandlerFactory::class, $di->lazyNew(MezzioContainer\WhoopsPageHandlerFactory::class));
        $di->set('Mezzio\WhoopsPageHandler', $di->lazyGetCall(MezzioContainer\WhoopsPageHandlerFactory::class, '__invoke', $di));

        // Error Handling

        // If in development:
        $di->set(MezzioContainer\WhoopsErrorHandlerFactory::class, $di->lazyNew(MezzioContainer\WhoopsErrorHandlerFactory::class));
        $di->set('Mezzio\FinalHandler', $di->lazyGetCall(MezzioContainer\WhoopsErrorHandlerFactory::class, '__invoke', $di));

        // If in production:
        // $di->set('Mezzio\FinalHandler', $di->lazyGetCall(MezzioContainer\TemplatedErrorHandlerFactory::class, '__invoke', $di));
    }

    public function modify(Container $di)
    {
        /*
        $router = $di->get(RouterInterface::class);
        $router->addRoute(new Route('/hello/{name}', function ($request, $response, $next) {
            $escaper = new Escaper();
            $name = $request->getAttribute('name', 'World');
            $response->getBody()->write('Hello ' . $escaper->escapeHtml($name));
            return $response;
        }, Route::HTTP_METHOD_ANY, 'hello'));
        */
    }
}
```

Your bootstrap (typically `public/index.php`) will then look like this:

```php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';
$container = require 'config/services.php';
$app = $container->get('Mezzio\Application');
$app->run();
```
