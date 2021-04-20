# mezzio

[![Build Status](https://github.com/mezzio/mezzio/workflows/Continuous%20Integration/badge.svg)](https://github.com/mezzio/mezzio/actions?query=workflow%3A"Continuous+Integration")

*Develop PSR-7 middleware applications in minutes!*

mezzio builds on [laminas-stratigility](https://github.com/laminas/laminas-stratigility)
to provide a minimalist PSR-7 middleware framework for PHP, with the following
features:

- Routing. Choose your own router; we support:
    - [Aura.Router](https://github.com/auraphp/Aura.Router)
    - [FastRoute](https://github.com/nikic/FastRoute)
    - [laminas-router](https://github.com/mezzio/mezzio-router)
- DI Containers, via [PSR-11 Container](https://github.com/php-fig/container).
  Middleware matched via routing is retrieved from the composed container.
- Optionally, templating. We support:
    - [Plates](http://platesphp.com/)
    - [Twig](http://twig.sensiolabs.org/)
    - [Laminas's PhpRenderer](https://github.com/laminas/laminas-view)

## Installation

We provide two ways to install Mezzio, both using
[Composer](https://getcomposer.org): via our
[skeleton project and installer](https://github.com/mezzio/mezzio-skeleton),
or manually.

### Using the skeleton + installer

The simplest way to install and get started is using the skeleton project, which
includes installer scripts for choosing a router, dependency injection
container, and optionally a template renderer and/or error handler. The skeleton
also provides configuration for officially supported dependencies.

To use the skeleton, use Composer's `create-project` command:

```bash
$ composer create-project mezzio/mezzio-skeleton <project dir>
```

This will prompt you through choosing your dependencies, and then create and
install the project in the `<project dir>` (omitting the `<project dir>` will
create and install in a `mezzio-skeleton/` directory).

### Manual Composer installation

You can install Mezzio standalone using Composer:

```bash
$ composer require mezzio/mezzio
```

However, at this point, Mezzio is not usable, as you need to supply
minimally:

- a router.
- a dependency injection container.

We currently support and provide the following routing integrations:

- [Aura.Router](https://github.com/auraphp/Aura.Router):
  `composer require mezzio/mezzio-aurarouter`
- [FastRoute](https://github.com/nikic/FastRoute):
  `composer require mezzio/mezzio-fastroute`
- [laminas-router](https://github.com/mezzio/mezzio-router):
  `composer require mezzio/mezzio-laminasrouter`

We recommend using a dependency injection container, and typehint against
[PSR-11 Container](https://github.com/php-fig/container). We
can recommend the following implementations:

- [laminas-servicemanager](https://github.com/laminas/laminas-servicemanager):
  `composer require laminas/laminas-servicemanager`
- [Pimple](https://github.com/silexphp/Pimple) (see [docs](docs/book/features/container/pimple.md) for more details):
  `composer require laminas/laminas-pimple-config`
- [Aura.Di](https://github.com/auraphp/Aura.Di) (see [docs](docs/book/features/container/aura-di.md) for more details):
  `composer require laminas/laminas-auradi-config`

Additionally, you may optionally want to install a template renderer
implementation, and/or an error handling integration. These are covered in the
documentation.

## Documentation

Documentation is [in the doc tree](docs/book/), and can be compiled using [mkdocs](https://www.mkdocs.org):

```bash
$ mkdocs build
```

Additionally, public-facing, browseable documentation is available at
https://docs.mezzio.dev/mezzio/
