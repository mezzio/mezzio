# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

## 3.2.0 - 2018-09-27

### Added

- [zendframework/zend-expressive#637](https://github.com/zendframework/zend-expressive/pull/637) adds support for zendframework/zend-diactoros 2.0.0. You may use either
  a 1.Y or 2.Y version of that library with Expressive applications.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#634](https://github.com/zendframework/zend-expressive/pull/634) provides several minor performance and maintenance improvements.

## 3.1.0 - 2018-07-30

### Added

- Nothing.

### Changed

- [zendframework/zend-expressive#629](https://github.com/zendframework/zend-expressive/pull/629) changes the constructor of `Mezzio\Middleware\ErrorResponseGenerator`
  to accept an additional, optional argument, `$layout`, which defaults to a new
  constant value, `ErrorResponseGenerator::LAYOUT_DEFAULT`, or `layout::default`.
  `Mezzio\Container\ErrorResponseGeneratorFactory` now also looks for
  the configuration value `mezzio.error_handler.layout`, and will use
  that value to seed the constructor argument. This change makes the
  `ErrorResponseGenerator` mirror the `NotFoundHandler`, allowing for a
  consistent layout between the two error pages.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.0.3 - 2018-07-25

### Added

- [zendframework/zend-expressive#615](https://github.com/zendframework/zend-expressive/pull/615) adds a cookbook entry for accessing common data in templates.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#627](https://github.com/zendframework/zend-expressive/pull/627) fixes an issue in the Whoops response generator; previously, if an error or
  exception occurred in an `ErrorHandler` listener or prior to handling the pipeline,
  Whoops would fail to intercept, resulting in an empty response with status 200. With
  the patch, it properly intercepts and displays the errors.

## 3.0.2 - 2018-04-10

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#612](https://github.com/zendframework/zend-expressive/pull/612) updates the
  `ApplicationConfigInjectionDelegator` delegator factory logic to cast the
  `$config` value to an array before passing it to its
  `injectPipelineFromConfig()` and `injectRoutesFromConfig()` methods, ensuring
  it will work correctly with containers that store the `config` service as an
  `ArrayObject` instead of an `array`.

## 3.0.1 - 2018-03-19

### Added

- Nothing.

### Changed

- [zendframework/zend-expressive#596](https://github.com/zendframework/zend-expressive/pull/596) updates the
  `ApplicationConfigInjectionDelegator::injectRoutesFromConfig()` method to use
  the key name associated with a route specification if no `name` member is
  provided when creating a `Route` instance. This can help enforce name
  uniqueness when defining routes via configuration.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 3.0.0 - 2018-03-15

### Added

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) adds support
  for the final PSR-15 interfaces, and explicitly depends on
  psr/http-server-middleware.

- [zendframework/zend-expressive#538](https://github.com/zendframework/zend-expressive/pull/538) adds scalar
  and return type hints to methods wherever possible.

- [zendframework/zend-expressive#562](https://github.com/zendframework/zend-expressive/pull/562) adds the
  class `Mezzio\Response\ServerRequestErrorResponseGenerator`, and maps
  it to the `Mezzio\Container\ServerRequestErrorResponseGeneratorFactory`.
  The class generates an error response when an exeption occurs producing a
  server request instance, and can be optionally templated.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) adds a new
  class, `Mezzio\MiddlewareContainer`. The class decorates a PSR-11
  `ContainerInterface`, and adds the following behavior:

  - If a class is not in the container, but exists, `has()` will return `true`.
  - If a class is not in the container, but exists, `get()` will attempt to
    instantiate it, caching the instance locally if it is valid.
  - Any instance pulled from the container or directly instantiated is tested.
    If it is a PSR-15 `RequestHandlerInterface`, it will decorate it in a
    laminas-stratigility `RequestHandlerMiddleware` instance. If the instance is
    not a PSR-15 `MiddlewareInterface`, the container will raise a
    `Mezzio\Exception\InvalidMiddlewareException`.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) adds a new
  class, `Mezzio\MiddlewareFactory`. The class composes a
  `MiddlewareContainer`, and exposes the following methods:

  - `callable(callable $middleware) : CallableMiddlewareDecorator`
  - `handler(RequestHandlerInterface $handler) : RequestHandlerMiddleware`
  - `lazy(string $service) : LazyLoadingMiddleware`
  - `prepare($middleware) : MiddlewareInterface`: accepts a string service name,
    callable, `RequestHandlerInterface`, `MiddlewareInterface`, or array of such
    values, and returns a `MiddlewareInterface`, raising an exception if it
    cannot determine what to do.
  - `pipeline(...$middleware) : MiddlewarePipe`: passes each argument to
    `prepare()`, and the result to `MiddlewarePipe::pipe()`, returning the
    pipeline when complete.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) adds
  the following factory classes, each within the `Mezzio\Container`
  namespace:

  - `ApplicationPipelineFactory`: creates and returns a
    `Laminas\Stratigility\MiddlewarePipe` to use as the application middleware
    pipeline.
  - `DispatchMiddlewareFactory`: creates and returns a `Mezzio\Router\DispatchMiddleware` instance.
  - `EmitterFactory`: creates and returns a
    `Laminas\HttpHandlerRunner\Emitter\EmitterStack` instance composing an
    `SapiEmitter` from that same namespace as the only emitter on the stack.
    This is used as a dependency for the `Laminas\HttpHandlerRunner\RequestHandlerRunner`
    service.
  - `MiddlewareContainerFactory`: creates and returns a `Mezzio\MiddlewareContainer`
    instance decorating the PSR-11 container passed to the factory.
  - `MiddlewareFactoryFactory`: creates and returns a `Mezzio\MiddlewareFactory`
    instance decorating a `MiddlewareContainer` instance as pulled from the
    container.
  - `RequestHandlerRunnerFactory`: creates and returns a
    `Laminas\HttpHandlerRunner\RequestHandlerRunner` instance, using the services
    `Mezzio\Application`, `Laminas\HttpHandlerRunner\Emitter\EmitterInterface`,
    `Mezzio\ServerRequestFactory`, and `Mezzio\ServerRequestErrorResponseGenerator`.
  - `ServerRequestFactoryFactory`: creates and returns a `callable` factory for
    generating a PSR-7 `ServerRequestInterface` instance; this returned factory is a
    dependency for the `Laminas\HttpHandlerRunner\RequestHandlerRunner` service.
  - `ServerRequestErrorResponseGeneratorFactory`: creates and returns a
    `callable` that accepts a PHP `Throwable` in order to generate a PSR-7
    `ResponseInterface` instance; this returned factory is a dependency for the
    `Laminas\HttpHandlerRunner\RequestHandlerRunner` service, which uses it to
    generate a response in the scenario that the `ServerRequestFactory` is
    unable to create a request instance.

- [zendframework/zend-expressive#551](https://github.com/zendframework/zend-expressive/pull/551) and
  [zendframework/zend-expressive#554](https://github.com/zendframework/zend-expressive/pull/554) add
  the following constants under the `Mezzio` namespace:

  - `DEFAULT_DELEGATE` can be used to refer to the former `DefaultDelegate` FQCN
    service, and maps to the `Mezzio\Handler\NotFoundHandler` service.
  - `IMPLICIT_HEAD_MIDDLEWARE` can be used to refer to the former
    `Mezzio\Middleware\ImplicitHeadMiddleware` service, and maps to the
    `Mezzio\Router\Middleware\ImplicitHeadMiddleware` service.
  - `IMPLICIT_OPTIONS_MIDDLEWARE` can be used to refer to the former
    `Mezzio\Middleware\ImplicitOptionsMiddleware` service, and maps to the
    `Mezzio\Router\Middleware\ImplicitOptionsMiddleware` service.
  - `NOT_FOUND_MIDDLEWARE` can be used to refer to the former
    `Mezzio\Middleware\NotFoundMiddleware` service, and maps to the
    `Mezzio\Handler\NotFoundHandler` service.

### Changed

- [zendframework/zend-expressive#579](https://github.com/zendframework/zend-expressive/pull/579) updates the
  version constraint for mezzio-router to use 3.0.0rc4 or later.

- [zendframework/zend-expressive#579](https://github.com/zendframework/zend-expressive/pull/579) updates the
  version constraint for laminas-stratigility to use 3.0.0rc1 or later.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) adds
  a dependency on zendframework/zend-httphandlerrunner 1.0.0

- [zendframework/zend-expressive#542](https://github.com/zendframework/zend-expressive/pull/542) modifies the
  `composer.json` to no longer suggest the pimple/pimple package, but rather the
  zendframework/zend-pimple-config package.

- [zendframework/zend-expressive#542](https://github.com/zendframework/zend-expressive/pull/542) modifies the
  `composer.json` to no longer suggest the aura/di package, but rather the
  zendframework/zend-auradi-config package.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) updates the
  `Mezzio\ConfigProvider` to reflect new, removed, and updated services
  and their factories.

- [zendframework/zend-expressive#554](https://github.com/zendframework/zend-expressive/pull/554) updates
  the `ConfigProvider` to add entries for the following constants as follows:

  - `IMPLICIT_HEAD_MIDDLEWARE` aliases to the `Mezzio\Router\Middleware\ImplicitHeadMiddleware` service.
  - `IMPLICIT_OPTIONS_MIDDLEWARE` aliases to the `Mezzio\Router\Middleware\ImplicitOptionsMiddleware` service.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) updates
  `Mezzio\Handler\NotFoundHandler` to implement the PSR-15
  `RequestHandlerInterface`. As `Mezzio\Middleware\NotFoundHandler` is
  removed, `Mezzio\Container\NotFoundHandlerFactory` has been
  re-purposedto create an instance of `Mezzio\Handler\NotFoundHandler`.

- [zendframework/zend-expressive#561](https://github.com/zendframework/zend-expressive/pull/561) modifies the
  `Mezzio\Handler\NotFoundHandler` to compose a response factory
  instead of a response prototype.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) refactors
  `Mezzio\Application` completely.

  The class no longer extends `Laminas\Stratigility\MiddlewarePipe`, and instead
  implements the PSR-15 `MiddlewareInterface` and `RequestHandlerInterface`.

  It now **requires** the following dependencies via constructor injection, in
  the following order:

  - `Mezzio\MiddlewareFactory`
  - `Laminas\Stratigility\MiddlewarePipe`; this is the pipeline representing the application.
  - `Mezzio\Router\RouteCollector`
  - `Laminas\HttpHandlerRunner\RequestHandlerRunner`

  It removes all "getter" methods (as detailed in the "Removed" section of this
  release), but retains the following methods, with the changes described below.
  Please note: in most cases, these methods accept the same arguments as in the
  version 2 series, with the exception of callable double-pass middleware (these
  may be decorated manually using `Laminas\Stratigility\doublePassMiddleware()`),
  and http-interop middleware (no longer supported; rewrite as PSR-15
  middleware).

  - `pipe($middlewareOrPath, $middleware = null) : void` passes its arguments to
    the composed `MiddlewareFactory`'s `prepare()` method; if two arguments are
    provided, the second is passed to the factory, and the two together are
    passed to `Laminas\Stratigility\path()` in order to decorate them to work as
    middleware.  The prepared middleware is then piped to the composed
    `MiddlewarePipe` instance.

    As a result of switching to use the `MiddlewareFactory` to prepare
    middleware, you may now pipe `RequestHandlerInterface` instances as well.

  - `route(string $path, $middleware, array $methods = null, string $name) : Route`
    passes its `$middleware` argument to the `MiddlewareFactory::prepare()`
    method, and then all arguments to the composed `RouteCollector` instance's
    `route()` method.

    As a result of switching to use the `MiddlewareFactory` to prepare
    middleware, you may now route to `RequestHandlerInterface` instances as
    well.

  - Each of `get`, `post`, `patch`, `put`, `delete`, and `any` now proxy to
    `route()` after marshaling the correct `$methods`.

  - `getRoutes() : Route[]` proxies to the composed `RouteCollector` instance.

  - `handle(ServerRequestInterface $request) : ResponseInterface` proxies to the
    composed `MiddlewarePipe` instance's `handle()` method.

  - `process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface`
    proxies to the composed `MiddlewarePipe` instance's `process()` method.

  - `run() : void` proxies to the composed `RequestHandlerRunner` instance.
    Please note that the method no longer accepts any arguments.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) modifies the
  `Mezzio\Container\ApplicationFactory` to reflect the changes to the
  `Mezzio\Application` class as detailed above. It pulls the following
  services to inject via the constructor:

  - `Mezzio\MiddlewareFactory`
  - `Laminas\Stratigility\ApplicationPipeline`, which should resolve to a
    `MiddlewarePipe` instance; use the
    `Mezzio\Container\ApplicationPipelineFactory`.
  - `Mezzio\Router\RouteCollector`
  - `Laminas\HttpHandlerRunner\RequestHandlerRunner`

- [zendframework/zend-expressive#581](https://github.com/zendframework/zend-expressive/pull/581)
  changes how the `ApplicationConfigInjectionDelegator::injectPipelineFromConfig()`
  method works. Previously, it would auto-inject routing and dispatch middleware
  if routes were configured, but no `middleware_pipeline` was present.
  Considering that this method will always be called manually, this
  functionality was removed; the method now becomes a no-op if no
  `middleware_pipeline` is present.

- [zendframework/zend-expressive#568](https://github.com/zendframework/zend-expressive/pull/568) updates the
  `ErrorHandlerFactory` to pull the `Psr\Http\Message\ResponseInterface`
  service, which returns a factory capable of returning a response instance,
  and passes it to the `Laminas\Stratigility\Middleware\ErrorHandler` instance it
  creates, as that class changes in 3.0.0alpha4 such that it now expects a
  factory instead of an instance.

- [zendframework/zend-expressive#562](https://github.com/zendframework/zend-expressive/pull/562) extracts
  most logic from `Mezzio\Middleware\ErrorResponseGenerator` to a new
  trait, `Mezzio\Response\ErrorResponseGeneratorTrait`. A trait was
  used as the classes consuming it are from different namespaces, and thus
  different inheritance trees. The trait is used by both the
  `ErrorResponseGenerator` and the new `ServerRequestErrorResponseGenerator`.

- [zendframework/zend-expressive#551](https://github.com/zendframework/zend-expressive/pull/551) removes
  `Mezzio\Container\RouteMiddlewareFactory`, as mezzio-router
  now provides a factory for the middleware.

- [zendframework/zend-expressive#551](https://github.com/zendframework/zend-expressive/pull/551) removes
  `Mezzio\Container\DispatchMiddlewareFactory`, as mezzio-router
  now provides a factory for the middleware.

- [zendframework/zend-expressive#551](https://github.com/zendframework/zend-expressive/pull/551) removes
  `Mezzio\Middleware\ImplicitHeadMiddleware`, as it is now provided by
  the mezzio-router package.

- [zendframework/zend-expressive#551](https://github.com/zendframework/zend-expressive/pull/551) removes
  `Mezzio\Middleware\ImplicitOptionsMiddleware`, as it is now provided
  by the mezzio-router package.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive#529](https://github.com/zendframework/zend-expressive/pull/529) removes
  support for PHP versions prior to PHP 7.1.

- [zendframework/zend-expressive#529](https://github.com/zendframework/zend-expressive/pull/529) removes
  support for http-interop/http-middleware (previous PSR-15 iteration).

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) removes
  support for http-interop/http-server-middleware.

- [zendframework/zend-expressive#580](https://github.com/zendframework/zend-expressive/pull/580) removes
  laminas-diactoros as a requirement; all usages of it within the package are
  currently conditional on it being installed, and can be replaced easily with
  any other PSR-7 implementation at this time.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) removes the
  class `Mezzio\Delegate\NotFoundDelegate`; use
  `Mezzio\Handler\NotFoundHandler` instead.

- [zendframework/zend-expressive#546](https://github.com/zendframework/zend-expressive/pull/546) removes the
  service `Mezzio\Delegate\DefaultDelegate`, as there is no longer a
  concept of a default handler invoked by the application. Instead, developers
  MUST pipe a request handler or middleware at the innermost layer of the
  pipeline guaranteed to return a response; we recommend using
  `Mezzio\Handler\NotFoundHandler` for this purpose.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) removes the
  class `Mezzio\Middleware\RouteMiddleware`. Use the
  `RouteMiddleware` from mezzio-router instead.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) removes the
  class `Mezzio\Middleware\DispatchMiddleware`. Use the
  `DispatchMiddleware` from mezzio-router instead; the factory
  `Mezzio\Container\DispatchMiddlewareFactory` will return an instance
  for you.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) removes the
  class `Mezzio\Emitter\EmitterStack`; use the class
  `Laminas\HttpHandlerRunner\Emitter\EmitterStack` instead.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) removes the
  following methods from `Mezzio\Application`:

  - `pipeRoutingMiddleware()`: use `pipe(\Mezzio\Router\RouteMiddleware::class)` instead.
  - `pipeDispatchMiddleware()`: use `pipe(\Mezzio\Router\DispatchMiddleware::class)` instead.
  - `getContainer()`
  - `getDefaultDelegate()`: ensure you pipe middleware or a request handler
    capable of returning a response at the innermost layer;
    `Mezzio\Handler\NotFoundHandler` can be used for this.
  - `getEmitter()`: use the `Laminas\HttpHandlerRunner\Emitter\EmitterInterface` service from the container.
  - `injectPipelineFromConfig()`: use the new `ApplicationConfigInjectionDelegator` and/or the static method of the same name it defines.
  - `injectRoutesFromConfig()`: use the new `ApplicationConfigInjectionDelegator` and/or the static method of the same name it defines.

- [zendframework/zend-expressive#543](https://github.com/zendframework/zend-expressive/pull/543) removes the
  class `Mezzio\AppFactory`.

- The internal `Mezzio\MarshalMiddlewareTrait`,
  `Mezzio\ApplicationConfigInjectionTrait`, and
  `Mezzio\IsCallableMiddlewareTrait` have been removed.

### Fixed

- [zendframework/zend-expressive#574](https://github.com/zendframework/zend-expressive/pull/574) updates the
  classes `Mezzio\Exception\InvalidMiddlewareException` and
  `MissingDependencyException` to implement the
  [PSR-11](https://www.php-fig.org/psr/psr-11/) `ContainerExceptionInterface`.

## 2.2.0 - 2018-03-12

### Added

- [zendframework/zend-expressive#581](https://github.com/zendframework/zend-expressive/pull/581) adds the
  class `Mezzio\ConfigProvider`, and exposes it to the
  laminas-component-installer Composer plugin. We recommend updating your
  `config/config.php` to reference it, as well as the
  `Mezzio\Router\ConfigProvider` shipped with mezzio-router
  versions 2.4 and up.

- [zendframework/zend-expressive#581](https://github.com/zendframework/zend-expressive/pull/581) adds the
  class `Mezzio\Container\ApplicationConfigInjectionDelegator`. The
  class can act as a delegator factory, and, when enabled, will inject routes
  and pipeline middleware defined in configuration.

  Additionally, the class exposes two static methods:

  - `injectPipelineFromConfig(Application $app, array $config)`
  - `injectRoutesFromConfig(Application $app, array $config)`

  These may be called to modify an `Application` instance based on an array of
  configuration. See thd documentation for more details.

- [zendframework/zend-expressive#581](https://github.com/zendframework/zend-expressive/pull/581) adds the
  class `Mezzio\Handler\NotFoundHandler`; the class takes over the
  functionality previously provided in `Mezzio\Delegate\NotFoundDelegate`.

### Changed

- [zendframework/zend-expressive#581](https://github.com/zendframework/zend-expressive/pull/581) updates the
  minimum supported laminas-stratigility version to 2.2.0.

- [zendframework/zend-expressive#581](https://github.com/zendframework/zend-expressive/pull/581) updates the
  minimum supported mezzio-router version to 2.4.0.

### Deprecated

- [zendframework/zend-expressive#581](https://github.com/zendframework/zend-expressive/pull/581) deprecates
  the following classes and traits:

  - `Mezzio\AppFactory`: if you are using this, you will need to switch
    to direct usage of `Mezzio\Application` or a
    `Laminas\Stratigility\MiddlewarePipe` instance.

  - `Mezzio\ApplicationConfigInjectionTrait`: if you are using it, it is
    marked internal, and deprecated; it will be removed in version 3.

  - `Mezzio\Container\NotFoundDelegateFactory`: the `NotFoundDelegate`
    will be renamed to `Mezzio\Handler\NotFoundHandler` in version 3,
    making this factory obsolete.

  - `Mezzio\Delegate\NotFoundDelegate`: this class becomes
    `Mezzio\Handler\NotFoundHandler` in v3, and the new class is added in
    version 2.2 as well.

  - `Mezzio\Emitter\EmitterStack`: the emitter concept is extracted from
    laminas-diactoros to a new component, laminas-httphandlerrunner. This latter
    component is used in version 3, and defines the `EmitterStack` class. Unless
    you are extending it or interacting with it directly, this change should not
    affect you; the `Laminas\Diactoros\Response\EmitterInterface` service will be
    directed to the new class in that version.

  - `Mezzio\IsCallableInteropMiddlewareTrait`: if you are using it, it is
    marked internal, and deprecated; it will be removed in version 3.

  - `Mezzio\MarshalMiddlewareTrait`: if you are using it, it is marked
    internal, and deprecated; it will be removed in version 3.

  - `Mezzio\Middleware\DispatchMiddleware`: this functionality has been
    moved to mezzio-router, under the `Mezzio\Router\Middleware`
    namespace.

  - `Mezzio\Middleware\ImplicitHeadMiddleware`: this functionality has been
    moved to mezzio-router, under the `Mezzio\Router\Middleware`
    namespace.

  - `Mezzio\Middleware\ImplicitOptionsMiddleware`: this functionality has been
    moved to mezzio-router, under the `Mezzio\Router\Middleware`
    namespace.

  - `Mezzio\Middleware\NotFoundHandler`: this will be removed in
    version 3, where you can instead pipe `Mezzio\Handler\NotFoundHandler`
    directly instead.

  - `Mezzio\Middleware\RouteMiddleware`: this functionality has been
    moved to mezzio-router, under the `Mezzio\Router\Middleware`
    namespace.

- [zendframework/zend-expressive#581](https://github.com/zendframework/zend-expressive/pull/581) deprecates
  the following methods from `Mezzio\Application`:
  - `pipeRoutingMiddleware()`
  - `pipeDispatchMiddleware()`
  - `getContainer()`: this method is removed in version 3; container access will only be via the bootstrap.
  - `getDefaultDelegate()`: the concept of a default delegate is removed in version 3.
  - `getEmitter()`: emitters move to a different collaborator in version 3.
  - `injectPipelineFromConfig()` andd `injectRoutesFromConfig()` are methods
    defined by the `ApplicationConfigInjectionTrait`, which will be removed in
    version 3. Use the `ApplicationConfigInjectionDelegator` instead.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.1.1 - 2018-03-09

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#583](https://github.com/zendframework/zend-expressive/pull/583) provides a
  number of minor fixes and test changes to ensure the component works with the
  mezzio-router 2.4 version. In particular, configuration-driven routes
  will now work properly across all versions, without deprecation notices.

- [zendframework/zend-expressive#582](https://github.com/zendframework/zend-expressive/pull/582) fixes
  redirects in the documentation.

## 2.1.0 - 2017-12-11

### Added

- [zendframework/zend-expressive#480](https://github.com/zendframework/zend-expressive/pull/480) updates the
  `ImplicitHeadMiddleware` to add a request attribute indicating the request was
  originally generated for a `HEAD` request before delegating the request; you
  can now pull the attribute `Mezzio\Middleware\ImplicitHeadMiddleware::FORWARDED_HTTP_METHOD_ATTRIBUTE`
  in your own middleware in order to vary behavior in these scenarios.

### Changed

- [zendframework/zend-expressive#505](https://github.com/zendframework/zend-expressive/pull/505) modifies
  `Mezzio\Application` to remove implementation of `__call()` in favor
  of the following new methods:

  - `get($path, $middleware, $name = null)`
  - `post($path, $middleware, $name = null)`
  - `put($path, $middleware, $name = null)`
  - `patch($path, $middleware, $name = null)`
  - `delete($path, $middleware, $name = null)`

  This change is an internal implementation detail only, and will not affect
  existing implementations or extensions.

- [zendframework/zend-expressive#511](https://github.com/zendframework/zend-expressive/pull/511) modifies
  the `NotFoundDelegate` to accept an optional `$layout` argument to its
  constructor; the value defaults to `layout::default` if not provided. That
  value will be passed for the `layout` template variable when the delegate
  renders a template, allowing laminas-view users (and potentially other template
  systems) to customize the layout template used for reporting errors.

  You may provide the template via the configuration
  `mezzio.error_handler.layout`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 2.0.6 - 2017-12-11

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#534](https://github.com/zendframework/zend-expressive/pull/534) provides a
  fix for how it detects `callable` middleware. Previously, it relied on PHP's
  `is_callable()`, but that function can result in false positives when provided
  a 2-element array where the first element is an object, as the function does
  not verify that the second argument is a valid method of the first. We now
  implement additional verifications to prevent such false positives.

## 2.0.5 - 2017-10-09

### Added

- Nothing.

### Changed

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#521](https://github.com/zendframework/zend-expressive/pull/521) adds an
  explicit requirement on http-interop/http-middleware `^0.4.1` to the package.
  This is necessary as newer builds of laminas-stratigility instead depend on the
  metapackage webimpress/http-middleware-compatibility instead of the
  http-interop/http-middleware package — but middleware shipped in Mezzio
  requires it. This addition fixes problems due to missing http-middleware
  interfaces.

## 2.0.4 - 2017-10-09

### Added

- [zendframework/zend-expressive#508](https://github.com/zendframework/zend-expressive/pull/508) adds
  documentation covering `Mezzio\Helper\ContentLengthMiddleware`,
  introduced in mezzio-helpers 4.1.0.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#479](https://github.com/zendframework/zend-expressive/pull/479) fixes the
  `WhoopsErrorResponseGenerator::$whoops` dockblock Type to support Whoops 1
  and 2.
- [zendframework/zend-expressive#482](https://github.com/zendframework/zend-expressive/pull/482) fixes the
  `Application::$defaultDelegate` dockblock Type.
- [zendframework/zend-expressive#489](https://github.com/zendframework/zend-expressive/pull/489) fixes an
  edge case in the `WhoopsErrorHandler` whereby it would emit an error if
  `$_SERVER['SCRIPT_NAME']` did not exist. It now checks for that value before
  attempting to use it.

## 2.0.3 - 2017-03-28

### Added

- Nothing.

### Changed

- [zendframework/zend-expressive#468](https://github.com/zendframework/zend-expressive/pull/468) updates
  references to `DefaultDelegate::class` to instead use the string
  `'Mezzio\Delegate\DefaultDelegate'`; using the string makes it clear
  that the service name does not resolve to an actual class.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#476](https://github.com/zendframework/zend-expressive/pull/476) fixes the
  `WhoopsErrorResponseGenerator` to ensure it returns a proper error status
  code, instead of using a `200 OK` status.

## 2.0.2 - 2017-03-13

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#467](https://github.com/zendframework/zend-expressive/pull/467) fixes an
  issue when passing invokable, duck-typed, interop middleware to the
  application without registering it with the container. Prior to the patch, it
  was incorrectly being decorated by
  `Laminas\Stratigility\Middleware\CallableMiddlewareWrapper` instead of
  `Laminas\Stratigility\Middleware\CallableInteropMiddlewareWrapper`.

## 2.0.1 - 2017-03-09

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#464](https://github.com/zendframework/zend-expressive/pull/464) fixes the
  `WhoopsErrorResponseGenerator` to provide a correct `Content-Type` header to
  the response when a JSON request occurs.

## 2.0.0 - 2017-03-07

### Added

- [zendframework/zend-expressive#450](https://github.com/zendframework/zend-expressive/pull/450) adds support
  for [PSR-11](http://www.php-fig.org/psr/psr-11/); Mezzio is now a PSR-11
  consumer.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) updates the
  laminas-stratigility dependency to require `^2.0`; this allows usage of both
  the new middleare-based error handling system introduced in laminas-stratigility
  1.3, as well as usage of [http-interop/http-middleware](https://github.com/http-interop/http-middleware)
  implementations with Mezzio. The following middleware is now supported:
  - Implementations of `Interop\Http\ServerMiddleware\MiddlewareInterface`.
  - Callable middleware that implements the same signature as
    `Interop\Http\ServerMiddleware\MiddlewareInterface`.
  - Callable middleware using the legacy double-pass signature
    (`function ($request, $response, callable $next)`); these are now decorated
    in `Laminas\Stratigility\Middleware\CallableMiddlewareWrapper` instances.
  - Service names resolving to any of the above.
  - Arrays of any of the above; these will be cast to
    `Laminas\Stratigility\MiddlewarePipe` instances, piping each middleware.

- [zendframework/zend-expressive#396](https://github.com/zendframework/zend-expressive/pull/396) adds
  `Mezzio\Middleware\NotFoundHandler`, which provides a way to return a
  templated 404 response to users. This middleware should be used as innermost
  middleware. You may use the new `Mezzio\Container\NotFoundHandlerFactory`
  to generate the instance via your DI container.

- [zendframework/zend-expressive#396](https://github.com/zendframework/zend-expressive/pull/396) adds
  `Mezzio\Container\ErrorHandlerFactory`, for generating a
  `Laminas\Stratigility\Middleware\ErrorHandler` to use with your application.
  If a `Mezzio\Middleware\ErrorResponseGenerator` service is present in
  the container, it will be used to seed the `ErrorHandler` with a response
  generator. If you use this facility, you should enable the
  `mezzio.raise_throwables` configuration flag.

- [zendframework/zend-expressive#396](https://github.com/zendframework/zend-expressive/pull/396) adds
  `Mezzio\Middleware\ErrorResponseGenerator` and
  `Mezzio\Middleware\WhoopsErrorResponseGenerator`, which may be used
  with `Laminas\Stratigility\Middleware\ErrorHandler` to generate error responses.
  The first will generate templated error responses if a template renderer is
  composed, and the latter will generate Whoops output.
  You may use the new `Mezzio\Container\ErrorResponseGeneratorFactory`
  and `Mezzio\Container\WhoopsErrorResponseGeneratorFactory`,
  respectively, to create these instances; if you do, assign these to the
  service name `Mezzio\Middleware\ErrorResponseGenerator` to have them
  automatically registered with the `ErrorHandler`.

- [zendframework/zend-expressive#396](https://github.com/zendframework/zend-expressive/pull/396) adds
  `Mezzio\ApplicationConfigInjectionTrait`, which exposes two methods,
  `injectRoutesFromConfig()` and `injectPipelineFromConfig()`; this trait is now
  composed into the `Application` class. These methods allow you to configure an
  `Application` instance from configuration if desired, and are now used by the
  `ApplicationFactory` to configure the `Application` instance.

- [zendframework/zend-expressive#396](https://github.com/zendframework/zend-expressive/pull/396) adds
  a vendor binary, `vendor/bin/mezzio-tooling`, which will install (or
  uninstall) the [mezzio-tooling](https://github.com/zendframework/zend-expressive-tooling);
  this package provides migration tools for updating your application to use
  programmatic pipelines and the new error handling strategy, as well as tools
  for identifying usage of the legacy Stratigility request and response
  decorators and error middleware.

- [zendframework/zend-expressive#413](https://github.com/zendframework/zend-expressive/pull/413) adds the
  middleware `Mezzio\Middleware\ImplicitHeadMiddleware`; this
  middleware can be used to provide implicit support for `HEAD` requests when
  the matched route does not explicitly support the method.

- [zendframework/zend-expressive#413](https://github.com/zendframework/zend-expressive/pull/413) adds the
  middleware `Mezzio\Middleware\ImplicitOptionsMiddleware`; this
  middleware can be used to provide implicit support for `OPTIONS` requests when
  the matched route does not explicitly support the method; the returned 200
  response will also include an `Allow` header listing allowed HTTP methods for
  the URI.

- [zendframework/zend-expressive#426](https://github.com/zendframework/zend-expressive/pull/426) adds the
  method `Application::getRoutes()`, which will return the list of
  `Mezzio\Router\Route` instances currently registered with the
  application.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) adds the
  class `Mezzio\Delegate\NotFoundDelegate`, an
  `Interop\Http\ServerMiddleware\DelegateInterface` implementation. The class
  will return a 404 response; if a `TemplateRendererInterface` is available and
  injected into the delegate, it will provide templated contents for the 404
  response as well. We also provide `Mezzio\Container\NotFoundDelegateFactory`
  for providing an instance.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) adds the
  method `Mezzio\Application::getDefaultDelegate()`. This method will
  return the default `Interop\Http\ServerMiddleware\DelegateInterface` injected
  during instantiation, or, if none was injected, lazy load an instance of
  `Mezzio\Delegate\NotFoundDelegate`.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) adds the
  constants `DISPATCH_MIDDLEWARE` and `ROUTING_MIDDLEWARE` to
  `Mezzio\Application`; they have identical values to the constants
  previously defined in `Mezzio\Container\ApplicationFactory`.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) adds
  `Mezzio\Middleware\LazyLoadingMiddleware`; this essentially extracts
  the logic previously used within `Mezzio\Application` to provide
  container-based middleware to allow lazy-loading only when dispatched.

### Changes

- [zendframework/zend-expressive#440](https://github.com/zendframework/zend-expressive/pull/440) changes the
  `Mezzio\Application::__call($method, array $args)` signature; in
  previous versions, `$args` did not have a typehint. If you are extending the
  class and overriding this method, you will need to update your signature
  accordingly.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) updates
  `Mezzio\Container\ApplicationFactory` to ignore the
  `mezzio.raise_throwables` configuration setting; Stratigility 2.X no
  longer catches exceptions in its middleware dispatcher, making the setting
  irrelevant.

- [zendframework/zend-expressive#422](https://github.com/zendframework/zend-expressive/pull/422) updates the
  mezzio-router minimum supported version to 2.0.0.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) modifies the
  `Mezzio\Container\ApplicationFactory` constants `DISPATCH_MIDDLEWARE`
  and `ROUTING_MIDDLEWARE` to define themselves based on the constants of the
  same name now defined in `Mezzio\Application`.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) modifies the
  constructor of `Mezzio\Application`; the third argument was
  previously a nullable callable `$finalHandler`; it is now a nullable
  `Interop\Http\ServerMiddleware\DelegateInterface` with the name
  `$defaultDelegate`.

- [zendframework/zend-expressive#450](https://github.com/zendframework/zend-expressive/pull/450) modifies the
  signatures in several classes to typehint against [PSR-11](http://www.php-fig.org/psr/psr-11/)
  instead of [container-interop](https://github.com/container-interop/container-interop);
  these include:

  - `Mezzio\AppFactory::create()`
  - `Mezzio\Application::__construct()`
  - `Mezzio\Container\ApplicationFactory::__invoke()`
  - `Mezzio\Container\ErrorHandlerFactory::__invoke()`
  - `Mezzio\Container\ErrorResponseGeneratorFactory::__invoke()`
  - `Mezzio\Container\NotFoundDelegateFactory::__invoke()`
  - `Mezzio\Container\NotFoundHandlerFactory::__invoke()`
  - `Mezzio\Container\WhoopsErrorResponseGeneratorFactory::__invoke()`
  - `Mezzio\Container\WhoopsFactory::__invoke()`
  - `Mezzio\Container\WhoopsPageHandlerFactory::__invoke()`

- [zendframework/zend-expressive#450](https://github.com/zendframework/zend-expressive/pull/450) changes the
  interface inheritance of `Mezzio\Container\Exception\InvalidServiceException`
  to extend `Psr\Container\ContainerExceptionInterface` instead of
  `Interop\Container\Exception\ContainerException`.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) removes the
  following routing/dispatch methods from `Mezzio\Application`:
  - `routeMiddleware()`; this is now encapsulated in `Mezzio\Middleware\RouteMiddleware`.
  - `dispatchMiddleware()`; this is now encapsulated in `Mezzio\Middleware\DispatchMiddleware`.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) removes the
  various "final handler" implementations and related factories. Users should
  now use the "default delegates" as detailed in sections previous. Classes
  and methods removed include:
  - `Mezzio\Application::getFinalHandler()`
  - `Mezzio\TemplatedErrorHandler`
  - `Mezzio\WhoopsErrorHandler`
  - `Mezzio\Container\TemplatedErrorHandlerFactory`
  - `Mezzio\Container\WhoopsErrorHandlerFactory`

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) removes the
  `Mezzio\ErrorMiddlewarePipe` class, as laminas-stratigility 2.X no
  longer defines `Laminas\Stratigility\ErrorMiddlewareInterface` or has a concept
  of variant-signature error middleware. Use standard middleware to provide
  error handling now.

- [zendframework/zend-expressive#428](https://github.com/zendframework/zend-expressive/pull/428) removes the
  exception types `Mezzio\Container\Exception\InvalidArgumentException`
  (use `Mezzio\Exception\InvalidArgumentException` instead) and
  `Mezzio\Container\Exception\NotFoundException` (which was never used
  internally).

### Fixed

- Nothing.

## 1.1.1 - 2017-02-14

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#447](https://github.com/zendframework/zend-expressive/pull/447) fixes an
  error in the `ApplicationFactory` that occurs when the `config` service is an
  `ArrayObject`. Prior to the fix, `ArrayObject` configurations would cause a
  fatal error when injecting the pipeline and/or routes.

## 1.1.0 - 2017-02-13

### Added

- [zendframework/zend-expressive#309](https://github.com/zendframework/zend-expressive/pull/309) adds the
  ability to provide options with which to instantiate the `FinalHandler`
  instance, via the configuration:

  ```php
  [
      'final_handler' => [
          'options' => [ /* array of options */ ],
      ],
  ```

- [zendframework/zend-expressive#373](https://github.com/zendframework/zend-expressive/pull/373) adds interception
  of exceptions from the `ServerRequestFactory` for invalid request information in order
  to return `400` responses.

- [zendframework/zend-expressive#432](https://github.com/zendframework/zend-expressive/pull/432) adds two new
  configuration flags for use with `Mezzio\Container\ApplicationFactory`:
  - `mezzio.programmatic_pipelines`: when enabled, the factory will
    ignore the `middleware_pipeline` and `routes` configuration, allowing you to
    wire these programmatically instead. We recommend creating these in the
    files `config/pipeline.php` and `config/routes.php`, respectively, and
    modifying your `public/index.php` to `require` these files in statements
    immediately preceding the call to `$app->run()`.
  - `mezzio.raise_throwables`: when enabled, this will be used to
    notify laminas-stratigility's internal dispatcher to no longer catch
    exceptions/throwables, and instead allow them to bubble out. This allows you
    to write custom middleware for handling errors.

- [zendframework/zend-expressive#429](https://github.com/zendframework/zend-expressive/pull/429) adds
  `Mezzio\Application::getDefaultDelegate()` as a
  forwards-compatibility measure for the upcoming version 2.0.0. Currently,
  it proxies to `getFinalHandler()`.

- [zendframework/zend-expressive#435](https://github.com/zendframework/zend-expressive/pull/435) adds support
  for the 2.X versions of mezzio-router and the various router
  implementations. This change also allows usage of mezzio-helpers 3.X.

### Changed

- [zendframework/zend-expressive#429](https://github.com/zendframework/zend-expressive/pull/429) updates the
  minimum supported laminas-stratigility version to 1.3.3.

- [zendframework/zend-expressive#396](https://github.com/zendframework/zend-expressive/pull/396) updates the
  `Mezzio\Container\ApplicationFactory` to vary creation of the
  `Application` instance based on two new configuration variables:

  - `mezzio.programmatic_pipeline` will cause the factory to skip
    injection of the middleware pipeline and routes from configuration. It is
    then up to the developer to do so, or use the `Application` API to pipe
    middleware and/or add routed middleware.

  - `mezzio.raise_throwables` will cause the factory to call the new
    `raiseThrowables()` method exposed by `Application` (and inherited from
    `Laminas\Stratigility\MiddlewarePipe`). Doing so will cause the application to
    raise any `Throwable` or `Exception` instances caught, instead of catching
    them and dispatching them to (legacy) Stratigility error middleware.

### Deprecated

- [zendframework/zend-expressive#429](https://github.com/zendframework/zend-expressive/pull/429) deprecates
  the following methods and classes:
  - `Mezzio\Application::pipeErrorHandler()`; use the
    `raise_throwables` flag and standard middleware to handle errors instead.
  - `Mezzio\Application::routeMiddleware()`; this is extracted to a
    dedicated middleware class for 2.0.
  - `Mezzio\Application::dispatchMiddleware()`; this is extracted to a
    dedicated middleware class for 2.0.
  - `Mezzio\Application::getFinalHandler()` (this patch provides `getDefaultDelegate()` as a forwards-compatibility measure)
  - `Mezzio\Container\Exception\InvalidArgumentException`; this will be removed
    in 2.0.0, and places where it was used will instead throw
    `Mezzio\Exception\InvalidArgumentException`.
  - `Mezzio\Container\Exception\NotFoundException`; this exception is
    never thrown at this point.
  - `Mezzio\Container\TemplatedErrorHandlerFactory`
  - `Mezzio\Container\WhoopsErrorHandlerFactory`
  - `Mezzio\ErrorMiddlewarePipe`; Stratigility 1.3 deprecates its
    `Laminas\Stratigility\ErrorMiddlewareInterface`, and removes it in version 2.0.
    use the `raise_throwables` flag and standard middleware to handle errors
    instead.
  - `Mezzio\TemplatedErrorHandler`; the "final handler" concept is
    retired in Mezzio 2.0, and replaced with default delegates (classes
    implementing `Interop\Http\ServerMiddleware\DelegateInterface` that will be
    executed when the internal pipeline is exhausted, in order to guarantee a
    response). If you are using custom final handlers, you will need to rewrite
    them when adopting Mezzio 2.0.
  - `Mezzio\WhoopsErrorHandler`

### Removed

- [zendframework/zend-expressive#406](https://github.com/zendframework/zend-expressive/pull/406) removes the
  `RouteResultSubjectInterface` implementation from `Mezzio\Application`,
  per the deprecation prior to the 1.0 stable release.

### Fixed

- [zendframework/zend-expressive#442](https://github.com/zendframework/zend-expressive/pull/442) fixes how
  the `WhoopsFactory` disables JSON output for whoops; previously, providing
  boolean `false` values for either of the configuration flags
  `json_exceptions.show_trace` or `json_exceptions.ajax_only` would result in
  enabling the settings; these flags are now correctly evaluated by the
  `WhoopsFactory`.

## 1.0.6 - 2017-01-09

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#420](https://github.com/zendframework/zend-expressive/pull/420) fixes the
  `routeMiddleware()`'s handling of 405 errors such that it now no longer emits
  deprecation notices when running under the Stratigility 1.3 series.

## 1.0.5 - 2016-12-08

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#403](https://github.com/zendframework/zend-expressive/pull/403) updates the
  `AppFactory::create()` logic to raise exceptions in either of the following
  scenarios:
  - no container is specified, and the class `Laminas\ServiceManager\ServiceManager`
    is not available.
  - no router is specified, and the class `Mezzio\Router\FastRouteRouter`
    is not available.
- [zendframework/zend-expressive#405](https://github.com/zendframework/zend-expressive/pull/405) fixes how
  the `TemplatedErrorHandler` injects templated content into the response.
  Previously, it would `write()` directly to the existing response body, which
  could lead to issues if previous middleware had written to the response (as
  the templated contents would append the previous contents). With this release,
  it now creates a new `Laminas\Diactoros\Stream`, writes to that, and returns a
  new response with that new stream, guaranteeing it only contains the new
  contents.
- [zendframework/zend-expressive#404](https://github.com/zendframework/zend-expressive/pull/404) fixes the
  `swallowDeprecationNotices()` handler such that it will not swallow a global
  handler once application execution completes.

## 1.0.4 - 2016-12-07

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#402](https://github.com/zendframework/zend-expressive/pull/402) fixes how
  `Application::__invoke()` registers the error handler designed to swallow
  deprecation notices, as introduced in 1.0.3. It now checks to see if another
  error handler was previously registered, and, if so, creates a composite
  handler that will delegate to the previous for all other errors.

## 1.0.3 - 2016-11-11

### Added

- Nothing.

### Changes

- [zendframework/zend-expressive#395](https://github.com/zendframework/zend-expressive/pull/395) updates
  `Application::__invoke()` to add an error handler to swallow deprecation
  notices due to triggering error middleware when using Stratigility 1.3+. Since
  error middleware is triggered whenever the `raiseThrowables` flag is not
  enabled and an error or empty queue situation is encountered, handling it this
  way prevents any such errors from bubbling out of the application.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 1.0.2 - 2016-11-11

### Added

- Nothing.

### Changes

- [zendframework/zend-expressive#393](https://github.com/zendframework/zend-expressive/pull/393) updates
  `Application::run()` to inject the request with an `originalResponse`
  attribute using the provided response as the value.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#393](https://github.com/zendframework/zend-expressive/pull/393) fixes how
  each of the `TemplatedErrorHandler` and `WhoopsErrorHandler` access the
  "original" request, URI, and/or response. Previously, these used
  Stratigility-specific methods; they now use request attributes, eliminating
  deprecation notices emitted in Stratigility 1.3+ versions.

## 1.0.1 - 2016-11-11

### Added

- [zendframework/zend-expressive#306](https://github.com/zendframework/zend-expressive/pull/306) adds a
  cookbook recipe covering flash messages.
- [zendframework/zend-expressive#384](https://github.com/zendframework/zend-expressive/pull/384) adds support
  for Whoops version 2 releases, providing PHP 7 support for Whoops.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#391](https://github.com/zendframework/zend-expressive/pull/391) fixes the
  `Application::run()` implementation to prevent emission of deprecation notices
  when used with Stratigility 1.3.

## 1.0.0 - 2016-01-28

Initial stable release.

### Added

- [zendframework/zend-expressive#279](https://github.com/zendframework/zend-expressive/pull/279) updates
  the documentation to provide automation for pushing to GitHub pages. As part
  of that work, documentation was re-organized, and a landing page provided.
  Documentation can now be found at: https://docs.mezzio.dev/mezzio/
- [zendframework/zend-expressive#299](https://github.com/zendframework/zend-expressive/pull/299) adds
  component-specific CSS to the documentation.
- [zendframework/zend-expressive#295](https://github.com/zendframework/zend-expressive/pull/295) adds
  support for handling PHP 7 engine exceptions in the templated and whoops final
  handlers.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#280](https://github.com/zendframework/zend-expressive/pull/280) fixes
  references to the `PlatesRenderer` in the error handling documentation.
- [zendframework/zend-expressive#284](https://github.com/zendframework/zend-expressive/pull/284) fixes
  the reference to maximebf/php-debugbar in the debug bar documentation.
- [zendframework/zend-expressive#285](https://github.com/zendframework/zend-expressive/pull/285) updates
  the section on mtymek/blast-base-url in the "Using a Base Path" cookbook
  recipe to conform to its latest release.
- [zendframework/zend-expressive#286](https://github.com/zendframework/zend-expressive/pull/286) fixes the
  documentation of the Composer "serve" command to correct a typo.
- [zendframework/zend-expressive#291](https://github.com/zendframework/zend-expressive/pull/291) fixes the
  documentation links to the RC5 -> v1 migration guide in both the CHANGELOG as
  well as the error messages emitted, ensuring users can locate the correct
  documentation in order to upgrade.
- [zendframework/zend-expressive#287](https://github.com/zendframework/zend-expressive/pull/287) updates the
  "standalone" quick start to reference calling `$app->pipeRoutingMiddleware()`
  and `$app->pipeDispatchMiddleware()` per the changes in RC6.
- [zendframework/zend-expressive#293](https://github.com/zendframework/zend-expressive/pull/293) adds
  a `require 'vendor/autoload.php';` line to the bootstrap script referenced in
  the laminas-servicemanager examples.
- [zendframework/zend-expressive#294](https://github.com/zendframework/zend-expressive/pull/294) updates the
  namespace referenced in the modulear-layout documentation to provide a better
  separation between the module/package/whatever, and the application consuming
  it.
- [zendframework/zend-expressive#298](https://github.com/zendframework/zend-expressive/pull/298) fixes a typo
  in a URI generation example.

## 1.0.0rc7 - 2016-01-21

Seventh release candidate.

### Added

- [zendframework/zend-expressive#277](https://github.com/zendframework/zend-expressive/pull/277) adds a new
  class, `Mezzio\ErrorMiddlewarePipe`. It composes a
  `Laminas\Stratigility\MiddlewarePipe`, but implements the error middleware
  signature via its own `__invoke()` method.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#277](https://github.com/zendframework/zend-expressive/pull/277) updates the
  `MarshalMiddlewareTrait` to create and return an `ErrorMiddlewarePipe` when
  the `$forError` argument provided indicates error middleware is expected.
  This fix allows defining arrays of error middleware via configuration.

## 1.0.0rc6 - 2016-01-18

Sixth release candidate.

This release contains backwards compatibility breaks with previous release
candidates. All previous functionality should continue to work, but will
emit `E_USER_DEPRECATED` notices prompting you to update your application.
In particular:

- The routing middleware has been split into two separate middleware
  implementations, one for routing, another for dispatching. This eliminates the
  need for the route result observer system, as middleware can now be placed
  *between* routing and dispatching — an approach that provides for greater
  flexibility with regards to providing route-based functionality.
- As a result of the above, `Mezzio\Application` no longer implements
  `Mezzio\Router\RouteResultSubjectInterface`, though it retains the
  methods associated (each emits a deprecation notice).
- Configuration for `Mezzio\Container\ApplicationFactory` was modified
  to implement the `middleware_pipeline` as a single queue, instead of
  segregating it between `pre_routing` and `post_routing`. Each item in the
  queue follows the original middleware specification from those keys, with one
  addition: a `priority` key can be used to allow you to granularly shape the
  execution order of the middleware pipeline.

A [migration guide](https://docs.mezzio.dev/mezzio/reference/migration/rc-to-v1/)
was written to help developers migrate to RC6 from earlier versions.

### Added

- [zendframework/zend-expressive#255](https://github.com/zendframework/zend-expressive/pull/255) adds
  documentation for the base path functionality provided by the `UrlHelper`
  class of mezzio-helpers.
- [zendframework/zend-expressive#227](https://github.com/zendframework/zend-expressive/pull/227) adds
  a section on creating localized routes, and setting the application locale
  based on the matched route.
- [zendframework/zend-expressive#244](https://github.com/zendframework/zend-expressive/pull/244) adds
  a recipe on using middleware to detect localized URIs (vs using a routing
  parameter), setting the application locale based on the match detected,
  and setting the `UrlHelper` base path with the same match.
- [zendframework/zend-expressive#260](https://github.com/zendframework/zend-expressive/pull/260) adds
  a recipe on how to add debug toolbars to your Mezzio applications.
- [zendframework/zend-expressive#261](https://github.com/zendframework/zend-expressive/pull/261) adds
  a flow/architectural diagram to the "features" chapter.
- [zendframework/zend-expressive#262](https://github.com/zendframework/zend-expressive/pull/262) adds
  a recipe demonstrating creating classes that can intercept multiple routes.
- [zendframework/zend-expressive#270](https://github.com/zendframework/zend-expressive/pull/270) adds
  new methods to `Mezzio\Application`:
  - `dispatchMiddleware()` is new middleware for dispatching the middleware
    matched by routing (this functionality was split from `routeMiddleware()`).
  - `routeResultObserverMiddleware()` is new middleware for notifying route
    result observers, and exists only to aid migration functionality; it is
    marked deprecated!
  - `pipeDispatchMiddleware()` will pipe the dispatch middleware to the
    `Application` instance.
  - `pipeRouteResultObserverMiddleware()` will pipe the route result observer
    middleware to the `Application` instance; like
    `routeResultObserverMiddleware()`, the method only exists for aiding
    migration, and is marked deprecated.
- [zendframework/zend-expressive#270](https://github.com/zendframework/zend-expressive/pull/270) adds
  `Mezzio\MarshalMiddlewareTrait`, which is composed by
  `Mezzio\Application`; it provides methods for marshaling
  middleware based on service names or arrays of services.

### Deprecated

- [zendframework/zend-expressive#270](https://github.com/zendframework/zend-expressive/pull/270) deprecates
  the following methods in `Mezzio\Application`, all of which will
  be removed in version 1.1:
  - `attachRouteResultObserver()`
  - `detachRouteResultObserver()`
  - `notifyRouteResultObservers()`
  - `pipeRouteResultObserverMiddleware()`
  - `routeResultObserverMiddleware()`

### Removed

- [zendframework/zend-expressive#270](https://github.com/zendframework/zend-expressive/pull/270) removes the
  `Mezzio\Router\RouteResultSubjectInterface` implementation from
  `Mezzio\Application`.
- [zendframework/zend-expressive#270](https://github.com/zendframework/zend-expressive/pull/270) eliminates
  the `pre_routing`/`post_routing` terminology from the `middleware_pipeline`,
  in favor of individually specified `priority` values in middleware
  specifications.

### Fixed

- [zendframework/zend-expressive#263](https://github.com/zendframework/zend-expressive/pull/263) typo
  fixes in documentation

## 1.0.0rc5 - 2015-12-22

Fifth release candidate.

### Added

- [zendframework/zend-expressive#233](https://github.com/zendframework/zend-expressive/pull/233) adds a
  documentation page detailing projects using and tutorials written on
  Mezzio.
- [zendframework/zend-expressive#238](https://github.com/zendframework/zend-expressive/pull/238) adds a
  cookbook recipe detailing how to handle serving an Mezzio application from
  a subdirectory of your web root.
- [zendframework/zend-expressive#239](https://github.com/zendframework/zend-expressive/pull/239) adds a
  cookbook recipe detailing how to create modular Mezzio applications.
- [zendframework/zend-expressive#243](https://github.com/zendframework/zend-expressive/pull/243) adds a
  chapter to the helpers section detailing the new `BodyParseMiddleware`.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#234](https://github.com/zendframework/zend-expressive/pull/234) fixes the
  inheritance tree for `Mezzio\Exception\RuntimeException` to inherit
  from `RuntimeException` and not `InvalidArgumentException`.
- [zendframework/zend-expressive#237](https://github.com/zendframework/zend-expressive/pull/237) updates the
  Pimple documentation to recommend `xtreamwayz/pimple-container-interop`
  instead of `mouf/pimple-interop`, as the latter consumed Pimple v1, instead of
  the current stable v3.

## 1.0.0rc4 - 2015-12-09

Fourth release candidate.

### Added

- [zendframework/zend-expressive#217](https://github.com/zendframework/zend-expressive/pull/217) adds a
  cookbook entry to the documentation detailing how to configure laminas-view
  helpers from other components, as well as how to add custom view helpers.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#219](https://github.com/zendframework/zend-expressive/pull/219) updates the
  "Hello World Using a Configuration-Driven Container" usage case to use
  laminas-stdlib's `Glob::glob()` instead of the `glob()` native function, to
  ensure the documented solution is portable across platforms.
- [zendframework/zend-expressive#223](https://github.com/zendframework/zend-expressive/pull/223) updates the
  documentation to refer to the `composer serve` command where relevant, and
  also details how to create the command for standalone users.
- [zendframework/zend-expressive#221](https://github.com/zendframework/zend-expressive/pull/221) splits the
  various cookbook entries into separate files, so each is self-contained.
- [zendframework/zend-expressive#224](https://github.com/zendframework/zend-expressive/pull/224) adds opening
  `<?php` tags to two configuration file examples, in order to prevent
  copy-paste errors.

## 1.0.0rc3 - 2015-12-07

Third release candidate.

### Added

- [zendframework/zend-expressive#185](https://github.com/zendframework/zend-expressive/pull/185)
  Support casting laminas-view models to arrays.
- [zendframework/zend-expressive#192](https://github.com/zendframework/zend-expressive/pull/192) adds support
  for specifying arrays of middleware both when routing and when creating
  pipeline middleware. This feature is opt-in and backwards compatible; simply
  specify an array value that does not resolve as a callable. Values in the
  array **must** be callables, service names resolving to callable middleware,
  or fully qualified class names that can be instantiated without arguments, and
  which result in invokable middleware.
- [zendframework/zend-expressive#200](https://github.com/zendframework/zend-expressive/pull/200),
  [zendframework/zend-expressive#206](https://github.com/zendframework/zend-expressive/pull/206), and
  [zendframework/zend-expressive#211](https://github.com/zendframework/zend-expressive/pull/211) add
  functionality for observing computed `RouteResult`s.
  `Mezzio\Application` now implements
  `Mezzio\Router\RouteResultSubjectInterface`, which allows attaching
  `Mezzio\RouteResultObserverInterface` implementations and notifying
  them of computed `RouteResult` instances. The following methods are now
  available on the `Application` instance:
  - `attachRouteResultObserver(Router\RouteResultObserverInterface $observer)`
  - `detachRouteResultObserver(Router\RouteResultObserverInterface $observer)`
  - `notifyRouteResultObservers(RouteResult $result)`; `Application` calls this
    internally within `routeMiddleware`.
  This feature enables the ability to notify objects of the calculated
  `RouteResult` without needing to inject middleware into the system.
- [zendframework/zend-expressive#81](https://github.com/zendframework/zend-expressive/pull/81) adds a
  cookbook entry for creating 404 handlers.
- [zendframework/zend-expressive#210](https://github.com/zendframework/zend-expressive/pull/210) adds a
  documentation section on the new [mezzio/mezzio-helpers](https://github.com/zendframework/zend-expressive-helpers)
  utilities.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive#204](https://github.com/zendframework/zend-expressive/pull/204) removes the
  `Router` and `Template` components, as they are now shipped with the following
  packages, respectively:
  - [mezzio/mezzio-router](https://github.com/mezzio/mezzio-router)
  - [mezzio/mezzio-template](https://github.com/zendframework/zend-expressive-template)
  This package has been updated to depend on each of them.

### Fixed

- [zendframework/zend-expressive#187](https://github.com/zendframework/zend-expressive/pull/187)
  Inject the route result as an attribute
- [zendframework/zend-expressive#197](https://github.com/zendframework/zend-expressive/pull/197) updates the
  `Mezzio\Container\ApplicationFactory` to raise exceptions in cases
  where received configuration is unusable, instead of silently ignoring it.
  This is a small backwards compatibility break, but is done to eliminate
  difficult to identify issues due to bad configuration.
- [zendframework/zend-expressive#202](https://github.com/zendframework/zend-expressive/pull/202) clarifies
  that `RouterInterface` implements **MUST** throw a `RuntimeException` if
  `addRoute()` is called after either `match()` or `generateUri()` have been
  called.

## 1.0.0rc2 - 2015-10-20

Second release candidate.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Updated branch aliases: dev-master => 1.0-dev, dev-develop => 1.1-dev.
- Point dev dependencies on sub-components to `~1.0-dev`.

## 1.0.0rc1 - 2015-10-19

First release candidate.

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.3 - 2015-10-19

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#160](https://github.com/zendframework/zend-expressive/pull/160) updates
  `EmitterStack` to throw a component-specific `InvalidArgumentException`
  instead of the generic SPL version.
- [zendframework/zend-expressive#163](https://github.com/zendframework/zend-expressive/pull/163) change the
  documentation on wiring middleware factories to put them in the `dependencies`
  section of `routes.global.php`; this keeps the routing and middleware
  configuration in the same file.

## 0.5.2 - 2015-10-17

### Added

- [zendframework/zend-expressive#158](https://github.com/zendframework/zend-expressive/pull/158) documents
  getting started via the [installer + skeleton](https://github.com/zendframework/zend-expressive-skeleton),
  and also documents "next steps" in terms of creating and wiring middleware
  when using the skeleton.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.5.1 - 2015-10-13

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#156](https://github.com/zendframework/zend-expressive/pull/156) updates how
  the routing middleware pulls middleware from the container; in order to work
  with laminas-servicemanager v3 and allow `has()` queries to query abstract
  factories, a second, boolean argument is now passed.

## 0.5.0 - 2015-10-10

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive#131](https://github.com/zendframework/zend-expressive/pull/131) modifies the
  repository to remove the concrete router and template renderer
  implementations, along with any related factories; these are now in their own
  packages. The classes removed include:
  - `Mezzio\Container\Template\PlatesRendererFactory`
  - `Mezzio\Container\Template\TwigRendererFactory`
  - `Mezzio\Container\Template\LaminasViewRendererFactory`
  - `Mezzio\Router\AuraRouter`
  - `Mezzio\Router\FastRouteRouter`
  - `Mezzio\Router\LaminasRouter`
  - `Mezzio\Template\PlatesRenderer`
  - `Mezzio\Template\TwigRenderer`
  - `Mezzio\Template\Twig\TwigExtension`
  - `Mezzio\Template\LaminasViewRenderer`
  - `Mezzio\Template\LaminasView\NamespacedPathStackResolver`
  - `Mezzio\Template\LaminasView\ServerUrlHelper`
  - `Mezzio\Template\LaminasView\UrlHelper`

### Fixed

- Nothing.

## 0.4.1 - TBD

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.

## 0.4.0 - 2015-10-10

### Added

- [zendframework/zend-expressive#132](https://github.com/zendframework/zend-expressive/pull/132) adds
  `Mezzio\Router\LaminasRouter`, replacing
  `Mezzio\Router\LaminasRouter`.
- [zendframework/zend-expressive#139](https://github.com/zendframework/zend-expressive/pull/139) adds:
  - `Mezzio\Template\TemplateRendererInterface`, replacing
    `Mezzio\Template\TemplateInterface`.
  - `Mezzio\Template\PlatesRenderer`, replacing
    `Mezzio\Template\Plates`.
  - `Mezzio\Template\TwigRenderer`, replacing
    `Mezzio\Template\Twig`.
  - `Mezzio\Template\LaminasViewRenderer`, replacing
    `Mezzio\Template\LaminasView`.
- [zendframework/zend-expressive#143](https://github.com/zendframework/zend-expressive/pull/143) adds
  the method `addDefaultParam($templateName, $param, $value)` to
  `TemplateRendererInterface`, allowing users to specify global and
  template-specific default parameters to use when rendering. To implement the
  feature, the patch also provides `Mezzio\Template\DefaultParamsTrait`
  to simplify incorporating the feature in implementations.
- [zendframework/zend-expressive#133](https://github.com/zendframework/zend-expressive/pull/133) adds a
  stipulation to `Mezzio\Router\RouterInterface` that `addRoute()`
  should *aggregate* `Route` instances only, and delay injection until `match()`
  and/or `generateUri()` are called; all shipped routers now follow this. This
  allows manipulating `Route` instances before calling `match()` or
  `generateUri()` — for instance, to inject options or a name.
- [zendframework/zend-expressive#133](https://github.com/zendframework/zend-expressive/pull/133) re-instates
  the `Route::setName()` method, as the changes to lazy-inject routes means that
  setting names and options after adding them to the application now works
  again.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive#132](https://github.com/zendframework/zend-expressive/pull/132) removes
  `Mezzio\Router\LaminasRouter`, renaming it to
  `Mezzio\Router\LaminasRouter`.
- [zendframework/zend-expressive#139](https://github.com/zendframework/zend-expressive/pull/139) removes:
  - `Mezzio\Template\TemplateInterface`, renaming it to
    `Mezzio\Template\TemplateRendererInterface`.
  - `Mezzio\Template\Plates`, renaming it to
    `Mezzio\Template\PlatesRenderer`.
  - `Mezzio\Template\Twig`, renaming it to
    `Mezzio\Template\TwigRenderer`.
  - `Mezzio\Template\LaminasView`, renaming it to
    `Mezzio\Template\LaminasViewRenderer`.

### Fixed

- Nothing.

## 0.3.1 - 2015-10-09

### Added

- [zendframework/zend-expressive#149](https://github.com/zendframework/zend-expressive/pull/149) adds
  verbiage to the `RouterInterface::generateUri()` method, specifying that the
  returned URI **MUST NOT** be escaped. The `AuraRouter` implementation has been
  updated to internally use `generateRaw()` to follow this guideline, and retain
  parity with the other existing implementations.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#140](https://github.com/zendframework/zend-expressive/pull/140) updates the
  AuraRouter to use the request method from the request object, and inject that
  under the `REQUEST_METHOD` server parameter key before passing the server
  parameters for matching. This simplifies testing.

## 0.3.0 - 2015-09-12

### Added

- [zendframework/zend-expressive#128](https://github.com/zendframework/zend-expressive/pull/128) adds
  container factories for each supported template implementation:
  - `Mezzio\Container\Template\PlatesFactory`
  - `Mezzio\Container\Template\TwigFactory`
  - `Mezzio\Container\Template\LaminasViewFactory`
- [zendframework/zend-expressive#128](https://github.com/zendframework/zend-expressive/pull/128) adds
  custom `url` and `serverUrl` laminas-view helper implementations, to allow
  integration with any router and with PSR-7 URI instances. The newly
  added `LaminasViewFactory` will inject these into the `HelperPluginManager` by
  default.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#128](https://github.com/zendframework/zend-expressive/pull/128) fixes an
  expectation in the `WhoopsErrorHandler` tests to ensure the tests can run
  successfully.

## 0.2.1 - 2015-09-10

### Added

- Nothing.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#125](https://github.com/zendframework/zend-expressive/pull/125) fixes the
  `WhoopsErrorHandler` to ensure it pushes the "pretty page handler" into the
  Whoops runtime.

## 0.2.0 - 2015-09-03

### Added

- [zendframework/zend-expressive#116](https://github.com/zendframework/zend-expressive/pull/116) adds
  `Application::any()` to complement the various HTTP-specific routing methods;
  it has the same signature as `get()`, `post()`, `patch()`, et al, but allows
  any HTTP method.
- [zendframework/zend-expressive#120](https://github.com/zendframework/zend-expressive/pull/120) renames the
  router classes for easier discoverability, to better reflect their usage, and
  for better naming consistency. `Aura` becomes `AuraRouter`, `FastRoute`
  becomes `FastRouteRouter` and `Laminas` becomes `LaminasRouter`.

### Deprecated

- Nothing.

### Removed

- [zendframework/zend-expressive#120](https://github.com/zendframework/zend-expressive/pull/120) removes the
  classes `Mezzio\Router\Aura`, `Mezzio\Router\FastRoute`, and
  `Mezzio\Router\Laminas`, per the "Added" section above.

### Fixed

- Nothing.

## 0.1.1 - 2015-09-03

### Added

- [zendframework/zend-expressive#112](https://github.com/zendframework/zend-expressive/pull/112) adds a
  chapter to the documentation on using Aura.Di (v3beta) with mezzio.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- [zendframework/zend-expressive#118](https://github.com/zendframework/zend-expressive/pull/118) fixes an
  issue whereby route options specified via configuration were not being pushed
  into generated `Route` instances before being passed to the underlying router.

## 0.1.0 - 2015-08-26

Initial tagged release.

### Added

- Everything.

### Deprecated

- Nothing.

### Removed

- Nothing.

### Fixed

- Nothing.
