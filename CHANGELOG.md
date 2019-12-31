# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
