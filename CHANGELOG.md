# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
