# Provided Factories

Mezzio provides several factories compatible with
[PSR-11 Container](https://www.php-fig.org/psr/psr-11/) to facilitate
setting up common dependencies. The following is a list of provided
containers, what they will create, the suggested service name, and any
additional dependencies they may require.

All factories, unless noted otherwise, are in the `Mezzio\Container`
namespace, and define an `__invoke()` method that accepts an
`Psr\Container\ContainerInterface` instance as the sole argument.

## ApplicationFactory

- **Provides**: `Mezzio\Application`
- **Suggested Name**: `Mezzio\Application`
- **Requires**: no additional services are required.
- **Optional**:
    - `Mezzio\Router\RouterInterface`. When provided, the service will
      be used to construct the `Application` instance; otherwise, an FastRoute router
      implementation will be used.
    - `Mezzio\Delegate\DefaultDelegate`. This should return an
      `Interop\Http\ServerMiddleware\DelegateInterface` instance to process
      when the middleware pipeline is exhausted without returning a response;
      by default, this will be a `Mezzio\Delegate\NotFoundDelegate`
      instance.
    - `Laminas\Diactoros\Response\EmitterInterface`. If none is provided, an instance
      of `Mezzio\Emitter\EmitterStack` composing a
      `Laminas\Diactoros\Response\SapiEmitter` instance will be used.
    - `config`, an array or `ArrayAccess` instance. This _may_ be used to seed the
      application instance with pipeline middleware and/or routed
      middleware (see more below).

Additionally, the container instance itself is injected into the `Application`
instance.

When the `config` service is present, the factory can utilize several keys in
order to seed the `Application` instance:

- `programmatic_pipeline` (bool) (Since 1.1.0): when enabled,
  `middleware_pipeline` and `routes` configuration are ignored, and the factory
  will assume that these are injected programmatically elsewhere.

- `raise_throwables` (bool) (Since 1.1.0; obsolete as of 2.0.0): when enabled,
  this flag will prevent the Stratigility middleware dispatcher from catching
  exceptions, and instead allow them to bubble outwards.

- `middleware_pipeline` can be used to seed the middleware pipeline:

  ```php
  'middleware_pipeline' => [
      // An array of middleware to register.
      [ /* ... */ ],

      // Mezzio 1.0:
      Mezzio\Container\ApplicationFactory::ROUTING_MIDDLEWARE,
      Mezzio\Container\ApplicationFactory::DISPATCH_MIDDLEWARE,

      // Mezzio 1.1 and above (above constants will still work, though):
      Mezzio\Application::ROUTING_MIDDLEWARE,
      Mezzio\Application::DISPATCH_MIDDLEWARE,

      [ /* ... */ ],
  ],
  ```

  Each item of the array, other than the entries for routing and dispatch
  middleware, must be an array itself, with the following structure:

  ```php
  [
      // required:
      'middleware' => 'Name of middleware service, valid middleware, or an array of these',
      // optional:
      'path'  => '/path/to/match',
      'priority' => 1, // Integer

      // optional under Mezzio 1.X; ignored under 2.X:
      'error' => false, // boolean
  ],
  ```

  The `middleware` key itself is the middleware to execute, and must be a
  service name resolving to valid middleware, middleware instances (either
  http-interop middleware or callable double-pass middleware), or an array of
  these values. If an array is provided, the specified middleware will be
  composed into a `Laminas\Stratigility\MiddlewarePipe` instance.

  If the `path` key is present, that key will be used to segregate the
  middleware to a specific matched path (in other words, it will not execute if
  the path is not matched).

  The `priority` defaults to 1, and follows the semantics of
  [SplPriorityQueue](http://php.net/SplPriorityQueue): higher integer values
  indicate higher priority (will execute earlier), while lower/negative integer
  values indicate lower priority (will execute last). Default priority is 1; use
  granular priority values to specify the order in which middleware should be
  piped to the application.

  You *can* specify keys for each middleware specification. These will be
  ignored by the factory, but can be useful when merging several configurations
  into one for the application.

  Under Mezzio 1.X, if the `error` key is present and boolean `true`, then
  the middleware will be registered as error middleware. (This is necessary due
  to the fact that the factory defines a callable wrapper around middleware to
  enable lazy-loading of middleware.) We recommend _not_ using this feature;
  see the chapter on [error handling](../error-handling.md) for details.

- `routes` is used to define routed middleware. The value must be an array,
  consisting of arrays defining each middleware:

  ```php
  'routes' => [
      [
          'path' => '/path/to/match',
          'middleware' => 'Middleware service name, valid middleware, or array of these values',
          'allowed_methods' => ['GET', 'POST', 'PATCH'],
          'options' => [
              'stuff' => 'to',
              'pass'  => 'to',
              'the'   => 'underlying router',
          ],
      ],
      // etc.
  ],
  ```

  Each route *requires*:

    - `path`: the path to match. Format will be based on the router you choose for
      your project.

    - `middleware`: a service name resolving to valid middleware, valid
      middleware (either http-interop middleware or callable double-pass
      middleware), or an array of such values (which will be composed into
      a `Laminas\Stratigility\MiddlewarePipe` instance); this middleware will be
      dispatched when the route matches.

  Optionally, the route definition may provide:

    - `allowed_methods`: an array of allowed HTTP methods. If not provided, the
      application assumes any method is allowed.

    - `name`: if not provided, the path will be used as the route name (and, if
      specific HTTP methods are allowed, a list of those).

    - `options`: a key/value set of additional options to pass to the underlying
      router implementation for the given route. (Typical use cases include
      passing constraints or default values.)

## ErrorHandlerFactory

- **Provides**: `Laminas\Stratigility\Middleware\ErrorHandler`
- **Suggested Name**: `Laminas\Stratigility\Middleware\ErrorHandler`
- **Requires**: no additional services are required.
- **Optional**:
    - `Mezzio\Middleware\ErrorResponseGenerator`. If not provided, the error
      handler will not compose an error response generator, making it largely
      useless other than to provide an empty response.

## ErrorResponseGeneratorFactory

- **Provides**: `Mezzio\Middleware\ErrorResponseGenerator`
- **Suggested Name**: `Laminas\Stratigility\Middleware\ErrorResponseGenerator`
- **Requires**: no additional services are required.
- **Optional**:
    - `Mezzio\Template\TemplateRendererInterface`. If not provided, the
      error response generator will provide a plain text response instead of a
      templated one.
    - `config`, an array or `ArrayAccess` instance. This will be used to seed the
      `ErrorResponseGenerator` instance with a template name to use for errors (see
      more below), and/or a "debug" flag value.

When the `config` service is present, the factory can utilize two values:

- `debug`, a flag indicating whether or not to provide debug information when
  creating an error response.
- `mezzio.error_handler.template_error`, a name of an alternate
  template to use (instead of the default represented in the
  `Mezzio\Middleware\ErrorResponseGenerator::TEMPLATE_DEFAULT`
  constant).

As an example:

```php
'debug' => true,
'mezzio' => [
    'error_handler' => [
        'template_error' => 'name of error template',
    ],
],
```

## NotFoundDelegateFactory

- **Provides**: `Mezzio\Delegate\NotFoundDelegate`
- **Suggested Name**: `Mezzio\Delegate\NotFoundDelegate`, and aliased
  to `Mezzio\Delegate\DefaultDelegate`.
- **Requires**: no additional services are required.
- **Optional**:
    - `Mezzio\Template\TemplateRendererInterface`. If not provided, the
      delegate will provide a plain text response instead of a templated one.
    - `config`, an array or `ArrayAccess` instance. This will be used to seed the
      `NotFoundDelegate` instance with a template name to use.

When the `config` service is present, the factory can utilize two values:

- `mezzio.error_handler.template_404`, a name of an alternate
  template to use (instead of the default represented in the
  `Mezzio\Delegate\NotFoundDelegate::TEMPLATE_DEFAULT` constant).

As an example:

```php
'mezzio' => [
    'error_handler' => [
        'template_404' => 'name of 404 template',
    ],
],
```

## NotFoundHandlerFactory

- **Provides**: `Mezzio\Middleware\NotFoundHandler`
- **Suggested Name**: `Mezzio\Middleware\NotFoundHandler`
- **Requires**: `Mezzio\Delegate\DefaultDelegate`

## WhoopsErrorResponseGeneratorFactory

- **Provides**: `Mezzio\Middleware\WhoopsErrorResponseGenerator`
- **Suggested Name**: `Mezzio\Middleware\ErrorResponseGenerator`
- **Requires**: `Mezzio\Whoops` (see [WhoopsFactory](#whoopsfactory),
below)

## WhoopsFactory

- **Provides**: `Whoops\Run`
- **Suggested Name**: `Mezzio\Whoops`
- **Requires**:
    - `Mezzio\WhoopsPageHandler`
- **Optional**:
    - `config`, an array or `ArrayAccess` instance. This will be used to seed
      additional page handlers, specifically the `JsonResponseHandler` (see
      more below).

This factory creates and configures a `Whoops\Run` instance so that it will work
properly with `Mezzio\Application`; this includes disabling immediate
write-to-output, disabling immediate quit, etc. The `PrettyPageHandler` returned
for the `Mezzio\WhoopsPageHandler` service will be injected.

It consumes the following `config` structure:

```php
'whoops' => [
    'json_exceptions' => [
        'display'    => true,
        'show_trace' => true,
        'ajax_only'  => true,
    ],
],
```

If no `whoops` top-level key is present in the configuration, a default instance
with no `JsonResponseHandler` composed will be created.

## WhoopsPageHandlerFactory

- **Provides**: `Whoops\Handler\PrettyPageHandler`
- **Suggested Name**: `Mezzio\WhoopsPageHandler`
- **Optional**:
    - `config`, an array or `ArrayAccess` instance. This will be used to further
      configure the `PrettyPageHandler` instance, specifically with editor
      configuration (for linking files such that they open in the configured
      editor).

It consumes the following `config` structure:

```php
'whoops' => [
    'editor' => 'editor name, editor service name, or callable',
],
```

The `editor` value must be a known editor name (see the Whoops documentation for
pre-configured editor types), a callable, or a service name to use.

## PlatesRendererFactory

- **Provides**: `Mezzio\Plates\PlatesRenderer`
- **FactoryName**: `Mezzio\Plates\PlatesRendererFactory`
- **Suggested Name**: `Mezzio\Template\TemplateRendererInterface`
- **Requires**: no additional services are required.
- **Optional**:
    - `config`, an array or `ArrayAccess` instance. This will be used to further
      configure the `Plates` instance, specifically with the filename extension
      to use, and paths to inject.

It consumes the following `config` structure:

```php
'templates' => [
    'extension' => 'file extension used by templates; defaults to html',
    'paths' => [
        // namespace / path pairs
        //
        // Numeric namespaces imply the default/main namespace. Paths may be
        // strings or arrays of string paths to associate with the namespace.
    ],
]
```

One note: Due to a limitation in the Plates engine, you can only map one path
per namespace when using Plates.

## TwigRendererFactory

- **Provides**: `Mezzio\Twig\TwigRenderer`
- **FactoryName**: `Mezzio\Twig\TwigRendererFactory`
- **Suggested Name**: `Mezzio\Template\TemplateRendererInterface`
- **Requires**: no additional services are required.
- **Optional**:
    - `Mezzio\Router\RouterInterface`; if found, it will be used to
      seed a `Mezzio\Twig\TwigExtension` instance for purposes
      of rendering application URLs.
    - `config`, an array or `ArrayAccess` instance. This will be used to further
      configure the `Twig` instance, specifically with the filename extension,
      paths to assets (and default asset version to use), and template paths to
      inject.

It consumes the following `config` structure:

```php
'debug' => boolean,
'templates' => [
    'cache_dir' => 'path to cached templates',
    'assets_url' => 'base URL for assets',
    'assets_version' => 'base version for assets',
    'extension' => 'file extension used by templates; defaults to html.twig',
    'paths' => [
        // namespace / path pairs
        //
        // Numeric namespaces imply the default/main namespace. Paths may be
        // strings or arrays of string paths to associate with the namespace.
    ],
]
```

When `debug` is true, it disables caching, enables debug mode, enables strict
variables, and enables auto reloading. The `assets_*` values are used to seed
the `TwigExtension` instance (assuming the router was found).

## LaminasViewRendererFactory

- **Provides**: `Mezzio\LaminasView\LaminasViewRenderer`
- **FactoryName**: `Mezzio\LaminasView\LaminasViewRendererFactory`
- **Suggested Name**: `Mezzio\Template\TemplateRendererInterface`
- **Requires**: no additional services are required.
    - `Mezzio\Router\RouterInterface`, in order to inject the custom
      url helper implementation.
- **Optional**:
    - `config`, an array or `ArrayAccess` instance. This will be used to further
      configure the `LaminasView` instance, specifically with the layout template
      name, entries for a `TemplateMapResolver`, and and template paths to
      inject.
    - `Laminas\View\HelperPluginManager`; if present, will be used to inject the
      `PhpRenderer` instance.

It consumes the following `config` structure:

```php
'templates' => [
    'layout' => 'name of layout view to use, if any',
    'map'    => [
        // template => filename pairs
    ],
    'paths'  => [
        // namespace / path pairs
        //
        // Numeric namespaces imply the default/main namespace. Paths may be
        // strings or arrays of string paths to associate with the namespace.
    ],
]
```

When creating the `PhpRenderer` instance, it will inject it with a
`Laminas\View\HelperPluginManager` instance (either pulled from the container, or
instantiated directly). It injects the helper plugin manager with custom url and
serverurl helpers, `Mezzio\LaminasView\UrlHelper` and
`Mezzio\LaminasView\ServerUrlHelper`, respetively.
