# Using laminas-router

[laminas-router](https://docs.laminas.dev/laminas-router/) provides several
router implementations used for Laminas+ applications; the default is
`Laminas\Router\Http\TreeRouteStack`, which can compose a number of different
routes of differing types in order to perform routing.

The Laminas bridge we provide, `Mezzio\Router\LaminasRouter`, uses the
`TreeRouteStack`, and injects `Segment` routes to it; these are in turn injected
with `Method` routes, and a special "method not allowed" route at negative
priority to enable us to distinguish between failure to match the path and
failure to match the HTTP method.

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

The package provides both a factory for the router, and a `ConfigProvider` that
wires the router with your application.

## Advanced configuration

If you want to provide custom setup or configuration, you can do so. In this
example, we will be defining two factories:

- A factory to register as and generate an `Laminas\Router\Http\TreeRouteStack`
  instance.
- A factory registered as `Mezzio\Router\RouterInterface`, which
  creates and returns a `Mezzio\Router\LaminasRouter` instance composing the
  `Laminas\Mvc\Router\Http\TreeRouteStack` instance.

The factories might look like the following:

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

```php
// in a config/autoload/ file, or within a ConfigProvider class:
return [
    'factories' => [
        \Laminas\Router\Http\TreeRouteStack::class => App\Container\TreeRouteStackFactory::class,
        \Mezzio\Router\RouterInterface::class => App\Container\RouterFactory::class,
    ],
];
```
