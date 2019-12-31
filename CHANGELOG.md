# Changelog

All notable changes to this project will be documented in this file, in reverse chronological order by release.

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
