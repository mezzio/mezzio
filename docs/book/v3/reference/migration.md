# Migration to Mezzio 3.0

Mezzio 3.0 should not result in many upgrade problems for users. However,
starting in this version, we offer a few changes affecting the following that
you should be aware of, and potentially update your application to adopt:

- [PHP 7.1 support](#php-7.1-support)
- [PSR-15 support](#psr-15-support)
- [New dependencies](#new-dependencies)
- [New features](#new-features)
- [Signature and behavior changes](#signature-and-behavior-changes)
- [Removed classes and traits](#removed-classes-and-traits)
- [Upgrading from v2](#upgrading)

## PHP 7.1 support

Starting in Mezzio 3.0 we support only PHP 7.1+.

## PSR-15 Support

All middleware and delegators now implement interfaces from
[PSR-15](https://www.php-fig.org/psr/psr-15) instead of
http-interop/http-middleware (a PSR-15 precursor). This means the following
changes were made throughout Mezzio:

- The `process()` method of all middleware now type hint the second argument
  against the PSR-15 `RequestHandlerInterface`, instead of the previous
  `DelegateInterface`.

- The `process()` method of all middleware now have a return type hint of
  `\Psr\Http\Message\ResponseInterface`.

- All "delegators" have become request handlers: these now implement the PSR-15
  interface `RequestHandlerInterface` instead of the former `DelegateInterface`.

- The `process()` method of handlers (formerly delegators) have been renamed to
  `handle()` and given a return type hint of
  `\Psr\Http\Message\ResponseInterface`.

This change also affects all middleware you, as an application developer, have
written, and your middleware will need to be update. We provide a tool for this
via mezzio-tooling. Make sure that package is up-to-date (a version 1
release should be installed), and run the following:

```php
$ ./vendor/bin/mezzio migrate:interop-middleware
```

This tool will locate any http-interop middleware and update it to PSR-15
middleware.

## New dependencies

Mezzio adds the following packages as dependencies:

- [psr/http-server-middleware](https://github.com/php-fig/http-server-middleware)
  provides the PSR-15 interfaces, and replaces the previous dependency on
  http-interop/http-middleware.

- [mezzio/mezzio-router](https://github.com/mezzio/mezzio-router);
  previously, we depended on this package indirectly; now it is a direct
  requirement.

- [mezzio/mezzio-tooling](https://github.com/mezzio/mezzio-tooling);
  this was suggested previously, but is now required as a development
  dependency.

- [laminas/laminas-httphandlerrunner](https://github.com/laminas/laminas-httphandlerrunner);
  this is now used for the purposes of marshaling the server request, dispatching
  the application, and emitting the response. The functionality is generalized
  enough to warrant a separate package.

## New features

The following classes were added in version 3:

- `Mezzio\Container\ApplicationConfigInjectionDelegator` is a
  [delegator factory](../features/container/delegator-factories.md) capable of
  piping and routing middleware from configuration. See the [recipe on
  autowiring routes and pipeline middleware](../cookbook/autowiring-routes-and-pipelines.md)
  for more information.

- `Mezzio\Container\ApplicationPipelineFactory` will produce an empty
  `MiddlewarePipe` for use with `Mezzio\Application`.

- `Mezzio\Container\EmitterFactory` will produce a
  `Laminas\HttpHandlerRunner\Emitter\EmitterStack` instance for use with the
  `RequestHandlerRunner` instance composed by the `Application`. See the
  [chapter on emitters](../features/emitters.md) for more details.

- `Mezzio\Container\MiddlewareContainerFactory` will produce a
  `MiddlewareContainer` composing the application container instance.

- `Mezzio\Container\MiddlewareFactoryFactory` will produce a
  `MiddlewareFactory` composing a `MiddlewareContainer` instance.

- `Mezzio\Container\RequestHandlerRunnerFactory` will produce a
  `Laminas\HttpHandlerRunner\RequestHandlerRunner` instance for use with the
  `Application` instance. See the [laminas-httphandlerrunner
  documentation](https://docs.laminas.dev/laminas-httphandlerrunner) for more
  details on this collaborator.

- `Mezzio\Container\ServerRequestErrorResponseGeneratorFactory` will
  produce a `Mezzio\Response\ServerRequestErrorResponseGenerator`
  instance for use with the `RequestHandlerRunner`.

- `Mezzio\Container\ServerRequestFactoryFactory` will produce a PHP
  callable capable of generating a PSR-7 `ServerRequestInterface` instance for use
  with the `RequestHandlerRunner`.

- `Mezzio\MiddlewareContainer` decorates a PSR-11 container, and
  ensures that the values pulled are PSR-15 `MiddlewareInterface` instances.
  If the container returns a PSR-15 `RequestHandlerInterface`, it decorates it
  via `Laminas\Stratigility\Middleware\RequestHandlerMiddleware`. All other types
  result in an exception being thrown.

- `Mezzio\MiddlewareFactory` allows creation of `MiddlewareInterface`
  instances from a variety of argument types, and is used by `Application` to
  allow piping and routing to middleware services, arrays of services, and more.
  It composes a `MiddlewareContainer` internally.

- `Mezzio\Response\ServerRequestErrorResponseGenerator` can act as a
  response generator for the `RequestHandlerRunner` when its composed server
  request factory raises an exception.

## Signature and behavior changes

The following signature changes were made that could affect _class extensions_
and/or consumers.

### Application

`Mezzio\Application` was refactored dramatically for version 3.

If you were instantiating it directly previously, the constructor arguments are
now, in order:

- `Mezzio\MiddlewareFactory`
- `Laminas\Stratigility\MiddlewarePipeInterface`
- `Mezzio\Router\RouteCollector`
- `Laminas\HttpHandlerRunner\RequestHandlerRunner`
- `Mezzio\Application::__construct(...)`

`Application` no longer supports piping or routing to double-pass middleware. If
you continue to need double-pass middleware (e.g., defined by a third-party
library), use `Laminas\Stratigility\doublePassMiddleware()` to decorate it prior to
piping or routing to it:

```php
use Laminas\Diactoros\Response;

use function Laminas\Stratigility\doublePassMiddleware;

$app->pipe(doublePassMiddleware($someDoublePassMiddleware, new Response()));

$app->get('/foo', doublePassMiddleware($someDoublePassMiddleware, new Response()));
```

Additionally, the following methods were **removed**:

- `pipeRoutingMiddleware()`: use `pipe(\Mezzio\Router\Middleware\RouteMiddleware::class)`
  instead.
- `pipeDispatchMiddleware()`: use `pipe(\Mezzio\Router\Middleware\DispatchMiddleware::class)`
  instead.
- `getContainer()`
- `getDefaultDelegate()`: ensure you pipe middleware or a request handler
  capable of returning a response at the innermost layer;
  `Mezzio\Handler\NotFoundHandler` can be used for this.
- `getEmitter()`: use the `Laminas\HttpHandlerRunner\Emitter\EmitterInterface` service from the container.
- `injectPipelineFromConfig()`: use the new `ApplicationConfigInjectionDelegator` and/or the static method of the same name it defines.
- `injectRoutesFromConfig()`: use the new `ApplicationConfigInjectionDelegator` and/or the static method of the same name it defines.

### ApplicationFactory

`Mezzio\Container\ApplicationFactory` no longer looks at the
`mezzio.programmatic_pipeline` flag, nor does it inject pipeline
middleware and/or routed middleware from configuration any longer.

If you want to use configuration-driven pipelines and/or middleware, you may
register the new class `Mezzio\Container\ApplicationConfigInjectionDelegator`
as a delegator factory on the `Mezzio\Application` service.

### NotFoundHandlerFactory

`Mezzio\Container\NotFoundHandlerFactory` now returns an instance of
`Mezzio\Handler\NotFoundHandler`, instead of
`Mezzio\Middleware\NotFoundHandler` (which has been removed).

### LazyLoadingMiddleware

`Mezzio\Middleware\LazyLoadingMiddleware` now composes a
`Mezzio\MiddlewareContainer` instance instead of a more general PSR-11
container; this is to ensure that the value returned is a PSR-15
`MiddlewareInterface` instance.

## Removed classes and traits

- `Mezzio\AppFactory` was removed. If you were using it previously,
  either use `Mezzio\Application` directly, or a
  `Laminas\Stratigility\MiddlewarePipe` instance.

- `Mezzio\ApplicationConfigInjectionTrait`; the functionality of this
  trait was replaced by the `Mezzio\Container\ApplicationConfigInjectionDelegator`.

- `Mezzio\Delegate\NotFoundDelegate`; use `Mezzio\Handler\NotFoundHandler`
  instead. Its factory, `Mezzio\Container\NotFoundDelegateFactory`, was
  also removed.

- `Mezzio\Emitter\EmitterStack`; use `Laminas\HttpHandlerRunner\Emitter\EmitterStack`
  instead.

- `Mezzio\IsCallableInteropMiddlewareTrait`; there is no functional
  equivalent, nor a need for this functionality as of version 3.

- `Mezzio\MarshalMiddlewareTrait`; the functionality of this trait was
  replaced by a combination of `Mezzio\MiddlewareContainer` and
  `Mezzio\MiddlewareFactory`.

- `Mezzio\Middleware\DispatchMiddleware`; use
  `Mezzio\Router\Middleware\DispatchMiddleware` instead.

- `Mezzio\Middleware\ImplicitHeadMiddleware`; use
  `Mezzio\Router\Middleware\ImplicitHeadMiddleware` instead.

- `Mezzio\Middleware\ImplicitOptionsMiddleware`; use
  `Mezzio\Router\Middleware\ImplicitOptionsMiddleware` instead.

- `Mezzio\Middleware\NotFoundHandler`; use `Mezzio\Handler\NotFoundHandler`
  instead.

- `Mezzio\Middleware\RouteMiddleware`; use
  `Mezzio\Router\Middleware\RouteMiddleware` instead.

## Upgrading

We provide a package you can add to your existing v2 application in order to
upgrade it to version 3.

Before installing and running the migration tooling, make sure you have checked
in your latest changes (assuming you are using version control), or have a
backup of your existing code.

Install the migration tooling using the following command:

```bash
$ composer require --dev mezzio/mezzio-migration
```

Once installed, run the following command to migrate your application:

```bash
$ ./vendor/bin/mezzio-migration migrate
```

This package does the following:

- Uninstalls all current dependencies (by removing the `vendor/` directory).
- Updates existing dependency constraints for known Mezzio packages to their
  latest stable versions. (See the tools [README](https://github.com/mezzio/mezzio-migration)
  for details on what versions of which packages the tool uses.)
- Adds development dependencies on laminas/laminas-component-installer and
  mezzio/mezzio-tooling.
- Updates the `config/pipeline.php` file to:
    - add strict type declarations.
    - modify it to return a callable, per the v3 skeleton.
    - update the middleware pipeline as follows:
        - `pipeRoutingMiddleware()` becomes a `pipe()` operation referencing the
          mezzio-router `RouteMiddleware`.
        - `pipeDispatchMiddleware()` becomes a `pipe()` operation referencing the
          mezzio-router `DispatchMiddleware`.
        - update references to `ImplicitHeadMiddleware` to reference the version
          in mezzio-router.
        - update references to `ImplicitOptionsMiddleware` to reference the version
          in mezzio-router.
        - update references to `Mezzio\Middleware\NotFoundHandler` to
          reference `Mezzio\Handler\NotFoundHandler`.
        - add a `pipe()` entry for the mezzio-router
          `MethodNotAllowedMiddleware`.
- Updates the `config/routes.php` file to:
    - add strict type declarations.
    - modify it to return a callable, per the v3 skeleton.
- Replaces the `public/index.php` file with the latest version from the skeleton.
- Updates `config/container.php` when Pimple or Aura.Di are in use:
    - For Pimple:
        - The package `xtreamwayz/pimple-container-interop` is replaced with
          `laminas/laminas-pimple-config`.
        - The Pimple variant of `container.php` from the v3 skeleton is used.
    - For Aura.Di
        - The package `aura/di` is replaced with `laminas/laminas-auradi-config`.
        - The Aura.Di variant of `container.php` from the v3 skeleton is used.
- Executes `./vendor/bin/mezzio migrate:interop-middleware`.
- Executes `./vendor/bin/mezzio migrate:middleware-to-request-handler`.
- Runs `./vendor/bin/phpcbf` if it is installed.

These steps should take care of most migration tasks.

It **does not** update unit tests. These cannot be automatically updated, due to
the amount of variance in testing strategies.

When done, use a diffing tool to compare and verify all changes. Please be aware
that the tool is not designed for edge cases; there may be things it does not do
or cannot catch within your code. When unsure, refer to the other sections in
this document to determine what else you may need to change.
