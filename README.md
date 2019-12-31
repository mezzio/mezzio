# mezzio

[![Build Status](https://travis-ci.org/mezzio/mezzio.svg?branch=master)](https://travis-ci.org/mezzio/mezzio)

*Begin developing PSR-7 middleware applications in minutes!*

**Note: This project is a work in progress. Don't use it in production!**

mezzio builds on [laminas-stratigility](https://github.com/laminas/laminas-stratigility)
to provide a minimalist PSR-7 middleware framework for PHP, with the following
features:

- Routing. Choose your own router; we support:
    - [Aura.Router](https://github.com/auraphp/Aura.Router)
    - [FastRoute](https://github.com/nikic/FastRoute)
    - [Laminas's MVC router](https://github.com/laminas/laminas-mvc)
- DI Containers, via [container-interop](https://github.com/container-interop/container-interop).
  Middleware matched via routing is retrieved from the composed container.
- Optionally, templating. We support:
    - [Plates](http://platesphp.com/)
    - [Twig](http://twig.sensiolabs.org/)
    - [Laminas's PhpRenderer](https://github.com/laminas/laminas-view)

## Installation

Install this library using composer:

```bash
$ composer require mezzio/mezzio:*@dev
```

You will also need a router. We currently support:

- [Aura.Router](https://github.com/auraphp/Aura.Router): `composer require aura/router`
- [FastRoute](https://github.com/nikic/FastRoute): `composer require nikic/fast-route`
- [Laminas MVC Router](https://github.com/laminas/laminas-mvc): `composer require laminas/laminas-mvc`

We recommend using a dependency injection container, and typehint against
[container-interop](https://github.com/container-interop/container-interop). We
can recommend the following implementations:

- [laminas-servicemanager](https://github.com/laminas/laminas-servicemanager):
  `composer require laminas/laminas-servicemanager`
- [pimple-interop](https://github.com/moufmouf/pimple-interop):
  `composer require mouf/pimple-interop`

## Documentation

Documentation is [in the doc tree](doc/), and can be compiled using [bookdown](http://bookdown.io):

```bash
$ bookdown doc/bookdown.json
$ php -S 0.0.0.0:8080 -t doc/html/ # then browse to http://localhost:8080/
```

> ### Bookdown
>
> You can install bookdown globally using `composer global require bookdown/bookdown`. If you do
> this, make sure that `$HOME/.composer/vendor/bin` is on your `$PATH`.

Additionally, public-facing, browseable documentation is available at
http://mezzio.rtfd.org.

## Architecture

Architectural notes are in [NOTES.md](NOTES.md).

Please see the tests for full information on capabilities.
