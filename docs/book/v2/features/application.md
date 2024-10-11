# Applications

In mezzio, you define a `Mezzio\Application` instance and
execute it. The `Application` instance is itself
[middleware](https://docs.laminas.dev/laminas-stratigility/middleware/)
that composes:

- a [router](router/intro.md), for dynamically routing requests to middleware.
- a [dependency injection container](container/intro.md), for retrieving
  middleware to dispatch.
- a [default delegate](error-handling.md#default-delegates) (Mezzio 2.X)
  or [final handler](error-handling.md#version-1-error-handling)
- an [emitter](https://docs.laminas.dev/laminas-diactoros/emitting-responses/),
  for emitting the response when application execution is complete.

You can define the `Application` instance in several ways:

- Direct instantiation, which requires providing several dependencies.
- The `AppFactory`, which will use some common defaults, but allows injecting alternate
  container and/or router implementations.
- Via a dependency injection container; we provide a factory for setting up all
  aspects of the instance via configuration and other defined services.

Regardless of how you set up the instance, there are several methods you will
likely interact with at some point or another.

## Instantiation

As noted at the start of this document, we provide several ways to create an
`Application` instance.

### Constructor

If you wish to manually instantiate the `Application` instance, it has the
following constructor:

```php
/**
 * @param Mezzio\Router\RouterInterface $router
 * @param null|Psr\Container\ContainerInterface $container IoC container from which to pull services, if any.
 * @param null|Interop\Http\ServerMiddleware\DelegateInterface $defaultDelegate
 *     Delegate to invoke when the internal middleware pipeline is exhausted
 *     without returning a response.
 * @param null|Laminas\Diactoros\Response\EmitterInterface $emitter Emitter to use when `run()` is
 *     invoked.
 */
public function __construct(
    Mezzio\Router\RouterInterface $router,
    Psr\Container\ContainerInterface $container = null,
    Interop\Http\ServerMiddleware\DelegateInterface $defaultDelegate = null,
    Laminas\Diactoros\Response\EmitterInterface $emitter = null
);
```

If no container is provided at instantiation, then all routed and piped
middleware **must** be provided as callables.

### AppFactory

- Deprecated since version 2.2; instantiate `Application` directly and/or use a
  `Laminas\Stratigility\MiddlewarePipe` instance instead.

`Mezzio\AppFactory` provides a convenience layer for creating an
`Application` instance; it makes the assumption that you will use defaults in
most situations, and likely only change which container and/or router you wish
to use. It has the following signature:

```php
AppFactory::create(
    Psr\Container\ContainerInterface $container = null,
    Mezzio\Router\RouterInterface $router = null
);
```

When no container or router are provided, it defaults to:

- laminas-servicemanager for the container.
- FastRoute for the router.

### Container factory

We also provide a factory that can be consumed by a
[PSR-11](https://www.php-fig.org/psr/psr-11/) dependency injection container;
see the [container factories documentation](container/factories.md) for details.

## Adding routable middleware

We [discuss routing vs piping elsewhere](router/piping.md); routing is the act
of dynamically matching an incoming request against criteria, and it is one of
the primary features of mezzio.

Regardless of which [router implementation](router/interface.md) you use, you
can use the following methods to provide routable middleware:

### route()

`route()` has the following signature:

```php
public function route(
    $pathOrRoute,
    $middleware = null,
    array $methods = null,
    $name = null
) : Mezzio\Router\Route
```

where:

- `$pathOrRoute` may be either a string path to match, or a
  `Mezzio\Router\Route` instance.
- `$middleware` **must** be present if `$pathOrRoute` is a string path, and
  **must** be:
    - a callable;
    - a service name that resolves to valid middleware in the container;
    - a fully qualified class name of a constructor-less class;
    - an array of any of the above; these will be composed in order into a
      `Laminas\Stratigility\MiddlewarePipe` instance.
- `$methods` must be an array of HTTP methods valid for the given path and
  middleware. If null, it assumes any method is valid.
- `$name` is the optional name for the route, and is used when generating a URI
  from known routes. See the section on [route naming](router/uri-generation.md#generating-uris)
  for details.

This method is typically only used if you want a single middleware to handle
multiple HTTP request methods.

### get(), post(), put(), patch(), delete(), any()

Each of the methods `get()`, `post()`, `put()`, `patch()`, `delete()`, and `any()`
proxies to `route()` and has the signature:

```php
function (
    $pathOrRoute,
    $middleware = null,
    $name = null
) : Mezzio\Router\Route
```

Essentially, each calls `route()` and specifies an array consisting solely of
the corresponding HTTP method for the `$methods` argument.

### Piping

Because mezzio builds on [laminas-stratigility](https://docs.laminas.dev/laminas-stratigility/),
and, more specifically, its `MiddlewarePipe` definition, you can also pipe
(queue) middleware to the application. This is useful for adding middleware that
should execute on each request, defining error handlers, and/or segregating
applications by subpath.

The signature of `pipe()` is:

```php
public function pipe($pathOrMiddleware, $middleware = null)
```

where:

- `$pathOrMiddleware` is either a string URI path (for path segregation), a
  callable middleware, or the service name for a middleware to fetch from the
  composed container.
- `$middleware` is required if `$pathOrMiddleware` is a string URI path. It can
  be one of:
    - a callable;
    - a service name that resolves to valid middleware in the container;
    - a fully qualified class name of a constructor-less class;
    - an array of any of the above; these will be composed in order into a
      `Laminas\Stratigility\MiddlewarePipe` instance.

Unlike `Laminas\Stratigility\MiddlewarePipe`, `Application::pipe()` *allows
fetching middleware by service name*. This facility allows lazy-loading of
middleware only when it is invoked. Internally, it wraps the call to fetch and
dispatch the middleware inside a closure.

Additionally, we define a new method, `pipeErrorHandler()`, with the following
signature:

```php
public function pipeErrorHandler($pathOrMiddleware, $middleware = null)
```

It acts just like `pipe()` except when the middleware specified is a service
name; in that particular case, when it wraps the middleware in a closure, it
uses the error handler signature:

```php
function ($error, ServerRequestInterface $request, ResponseInterface $response, callable $next);
```

Read the section on [piping vs routing](router/piping.md) for more information.

### Registering routing and dispatch middleware

Routing is accomplished via dedicated middleware, `Mezzio\Middleware\RouteMiddleware`;
similarly, dispatching of routed middleware has a corresponding middleware,
`Mezzio\Middleware\DispatchMiddleware`. Each can be piped/registered
with other middleware platforms if desired.

These methods **MUST** be piped to the application so that the application will
route and dispatch routed middleware. This is done using the following methods:

```php
$app->pipeRoutingMiddleware();
$app->pipeDispatchMiddleware();
```

See the section on [piping](router/piping.md) to see how you can register
non-routed middleware and create layered middleware applications.

> #### Changed in version 2.2
>
> Starting in version 2.2, the methods `pipeRoutingMiddleware` and
> `pipeDispatchMiddleware` are deprecated in favor of piping the middleware
> manually.
>
> Additionally, the middleware has been ported to the mezzio-router
> package, under the namespace `Mezzio\Router\Middleware`. We suggest
> piping them by service name. Please see our [migration documentation](../reference/migration-to-v2-2.md#routing-and-dispatch-middleware)
> for more details, and for information on how to automatically update your
> application.

## Retrieving dependencies

As noted in the intro, the `Application` class has several dependencies. Some of
these may allow further configuration, or may be useful on their own, and have
methods for retrieving them. They include:

- `getContainer()`: returns the composed [PSR-11 container](https://www.php-fig.org/psr/psr-11/)
  instance (used to retrieve routed middleware).
- `getEmitter()`: returns the composed
  [emitter](https://docs.laminas.dev/laminas-diactoros/emitting-responses/),
  typically a `Mezzio\Emitter\EmitterStack` instance.
- `getDefaultDelegate()`: retrieves the default delegate to use when the internal middleware pipeline is exhausted without returning a response. If none is provided at instantiation, this method will do one of the following:
    - If no container is composed, instantiates a
      `Mezzio\Delegate\NotFoundDelegate` using the composed response
      prototype only.
    - If a container is composed, but does not have the
      `Mezzio\Delegate\DefaultDelegate` service, it creates and invokes an
      instance of `Mezzio\Container\NotFoundDelegateFactory`, passing it
      the composed container, and uses the value created.
    - If a container is composed and contains the `Mezzio\Delegate\DefaultDelegate`
      service, it returns that.

<!-- markdownlint-disable-next-line heading-increment -->
> #### Deprecated
>
> Each of the above methods are deprecated starting in version 2.2, and will be
> removed in version 3.0.

## Executing the application: run()

When the application is completely setup, you can execute it with the `run()`
method. The method may be called with no arguments, but has the following
signature:

```php
public function run(
    ServerRequestInterface $request = null,
    ResponseInterface $response = null
);
```
