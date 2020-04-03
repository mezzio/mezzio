# Using the Laminas Router

[laminas-router](https://docs.laminas.dev/laminas-router/) provides several
router implementations used for Laminas+ applications; the default is
`Laminas\Router\Http\TreeRouteStack`, which can compose a number of different
routes of differing types in order to perform routing.

The Laminas bridge we provide, `Mezzio\Router\LaminasRouter`, uses the
`TreeRouteStack`, and injects `Segment` routes to it; these are in turn injected
with `Method` routes, and a special "method not allowed" route at negative
priority to enable us to distinguish between failure to match the path and
failure to match the HTTP method.

If you instantiate it with no arguments, it will create an empty
`TreeRouteStack`. Thus, the simplest way to start with this router is:

```php
use Mezzio\AppFactory;
use Mezzio\Router\LaminasRouter;

$app = AppFactory::create(null, new LaminasRouter());
```

The `TreeRouteStack` offers some unique features:

- Route "prototypes". These are essentially like child routes that must *also*
  match in order for a given route to match. These are useful for implementing
  functionality such as ensuring the request comes in over HTTPS, or over a
  specific subdomain.
- Base URL functionality. If a base URL is injected, comparisons will be
  relative to that URL. This is mostly unnecessary with Stratigility-based
  middleware, but could solve some edge cases.

To specify these, you need access to the underlying `TreeRouteStack`
instance, however, and the `RouterInterface` does not provide an accessor!

The answer, then, is to use dependency injection. This can be done in two ways:
programmatically, or via a factory to use in conjunction with your container
instance.

## Installing the Laminas Router

To use the Laminas router, you will need to install the laminas-mvc router integration:

```bash
$ composer require mezzio/mezzio-laminasrouter
```

## Quick Start

At its simplest, you can instantiate a `Mezzio\Router\LaminasRouter` instance
with no arguments; it will create the underlying laminas-mvc routing objects
required and compose them for you:

```php
use Mezzio\Router\LaminasRouter;

$router = new LaminasRouter();
```

## Programmatic Creation

If you need greater control over the laminas-mvc router setup and configuration,
you can create the instances necessary and inject them into
`Mezzio\Router\LaminasRouter` during instantiation.

```php
use Laminas\Router\Http\TreeRouteStack;
use Mezzio\AppFactory;
use Mezzio\Router\LaminasRouter;

$laminasRouter = new TreeRouteStack();
$laminasRouter->addPrototypes(/* ... */);
$laminasRouter->setBaseUrl(/* ... */);

$router = new LaminasRouter($laminasRouter);

// First argument is the container to use, if not using the default;
// second is the router.
$app = AppFactory::create(null, $router);
```

> ### Piping the route middleware
>
> As a reminder, you will need to ensure that middleware is piped in the order
> in which it needs to be executed; please see the section on "Controlling
> middleware execution order" in the [piping documentation](piping.md). This is
> particularly salient when defining routes before injecting the router in the
> application instance!

## Factory-Driven Creation

[We recommend using an Inversion of Control container](../container/intro.md)
for your applications; as such, in this section we will demonstrate
two strategies for creating your laminas-mvc router implementation.

### Basic Router

If you don't need to provide any setup or configuration, you can simply
instantiate and return an instance of `Mezzio\Router\LaminasRouter` for the
service name `Mezzio\Router\RouterInterface`.

A factory would look like this:

```php
// in src/App/Container/RouterFactory.php
namespace App\Container;

use Psr\Container\ContainerInterface;
use Mezzio\Router\LaminasRouter;

class RouterFactory
{
    /**
     * @param ContainerInterface $container
     * @return LaminasRouter
     */
    public function __invoke(ContainerInterface $container)
    {
        return new LaminasRouter();
    }
}
```

You would register this with laminas-servicemanager using:

```php
$container->setFactory(
    Mezzio\Router\RouterInterface::class,
    App\Container\RouterFactory::class
);
```

And in Pimple:

```php
$pimple[Mezzio\Router\RouterInterface::class] = new Application\Container\RouterFactory();
```

For laminas-servicemanager, you can omit the factory entirely, and register the
class as an invokable:

```php
$container->setInvokableClass(
    Mezzio\Router\RouterInterface::class,
    Mezzio\Router\LaminasRouter::class
);
```

### Advanced Configuration

If you want to provide custom setup or configuration, you can do so. In this
example, we will be defining two factories:

- A factory to register as and generate an `Laminas\Router\Http\TreeRouteStack`
  instance.
- A factory registered as `Mezzio\Router\RouterInterface`, which
  creates and returns a `Mezzio\Router\LaminasRouter` instance composing the
  `Laminas\Mvc\Router\Http\TreeRouteStack` instance.

Sound difficult? It's not; we've essentially done it above already!

```php
// in src/App/Container/TreeRouteStackFactory.php:
namespace App\Container;

use Psr\Container\ContainerInterface;
use Laminas\Http\Router\TreeRouteStack;

class TreeRouteStackFactory
{
    /**
     * @param ContainerInterface $container
     * @return TreeRouteStack
     */
    public function __invoke(ContainerInterface $container)
    {
        $router = new TreeRouteStack();
        $router->addPrototypes(/* ... */);
        $router->setBaseUrl(/* ... */);

        return $router;
    }
}

// in src/App/Container/RouterFactory.php
namespace App\Container;

use Psr\Container\ContainerInterface;
use Mezzio\Router\LaminasRouter;

class RouterFactory
{
    /**
     * @param ContainerInterface $container
     * @return LaminasRouter
     */
    public function __invoke(ContainerInterface $container)
    {
        return new LaminasRouter($container->get(Laminas\Mvc\Router\Http\TreeRouteStack::class));
    }
}
```

From here, you will need to register your factories with your IoC container.

If you are using laminas-servicemanager, this will look like:

```php
// Programmatically:
use Laminas\ServiceManager\ServiceManager;

$container = new ServiceManager();
$container->addFactory(
    Laminas\Router\Http\TreeRouteStack::class,
    App\Container\TreeRouteStackFactory::class
);
$container->addFactory(
    Mezzio\Router\RouterInterface::class,
    App\Container\RouterFactory::class
);

// Alternately, via configuration:
return [
    'factories' => [
        Laminas\Router\Http\TreeRouteStack::class => App\Container\TreeRouteStackFactory::class,
        Mezzio\Router\RouterInterface::class => App\Container\RouterFactory::class,
    ],
];
```

For Pimple, configuration looks like:

```php
use Application\Container\TreeRouteStackFactory;
use Application\Container\LaminasRouterFactory;
use Interop\Container\Pimple\PimpleInterop;

$container = new PimpleInterop();
$container[Laminas\Router\Http\TreeRouteStackFactory::class] = new TreeRouteStackFactory();
$container[Mezzio\Router\RouterInterface::class] = new RouterFactory();
```
