# mezzio

[![Build Status](https://github.com/mezzio/mezzio/workflows/Continuous%20Integration/badge.svg)](https://github.com/mezzio/mezzio/actions?query=workflow%3A"Continuous+Integration")
[![Type Coverage](https://shepherd.dev/github/mezzio/mezzio/coverage.svg)](https://shepherd.dev/github/mezzio/mezzio)

> ## 🇷🇺 Русским гражданам
>
> Мы, участники Laminas, родились и живем в разных странах. У многих из нас есть друзья, родственники и коллеги как в России, так и в Украине. Некоторые из нас родились в России. Некоторые из нас живут в России. У некоторых бабушки и дедушки сражались с фашистами во Второй мировой войне. Здесь никто не поддерживает фашизм.
>
> У одного из нас есть украинская родственница, которая спаслась из дома вместе с сыном. Поезд задержался из-за бомбежки на дороге впереди. У нас есть друзья, которые прячутся в бомбоубежищах. Мы с тревогой ждем весточки от них после воздушных налетов, которые беспорядочно наносят удары и попадают по больницам, школам, детским садам и домам. Мы не берем это из каких-либо СМИ. Мы наблюдаем это напрямую.
>
> Вы доверяете нам достаточно, чтоб использовать наши программы, и мы просим вас довериться нам вновь. Мы нуждаемся в помощи. Выходите и протестуйте против этой бесполезной войны. Остановите кровопролитие. Скажите "Нет войне!"
>
> ## 🇺🇸 To Citizens of Russia
>
> We at Laminas come from all over the world. Many of us have friends, family and colleagues in both Russia and Ukraine. Some of us were born in Russia. Some of us currently live in Russia. Some have grandparents who fought Nazis in World War II. Nobody here supports fascism.
>
> One team member has a Ukrainian relative who fled her home with her son. The train was delayed due to bombing on the road ahead. We have friends who are hiding in bomb shelters. We anxiously follow up on them after the air raids, which indiscriminately fire at hospitals, schools, kindergartens and houses. We're not taking this from any media. These are our actual experiences.
>
> You trust us enough to use our software. We ask that you trust us to say the truth on this. We need your help. Go out and protest this unnecessary war. Stop the bloodshed. Say "stop the war!"

*Develop PSR-7 middleware applications in minutes!*

mezzio builds on [laminas-stratigility](https://github.com/laminas/laminas-stratigility)
to provide a minimalist PSR-7 middleware framework for PHP, with the following
features:

- Routing. Choose your own router; we support:
    - [FastRoute](https://github.com/nikic/FastRoute)
    - [laminas-router](https://github.com/mezzio/mezzio-router)
- DI Containers, via [PSR-11 Container](https://github.com/php-fig/container).
  Middleware matched via routing is retrieved from the composed container.
- Optionally, templating. We support:
    - [Plates](http://platesphp.com/)
    - [Twig](https://twig.symfony.com/)
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
composer create-project mezzio/mezzio-skeleton <project dir>
```

This will prompt you through choosing your dependencies, and then create and
install the project in the `<project dir>` (omitting the `<project dir>` will
create and install in a `mezzio-skeleton/` directory).

### Manual Composer installation

You can install Mezzio standalone using Composer:

```bash
composer require mezzio/mezzio
```

However, at this point, Mezzio is not usable, as you need to supply
minimally:

- a router.
- a dependency injection container.

We currently support and provide the following routing integrations:

- [FastRoute](https://github.com/nikic/FastRoute):
  `composer require mezzio/mezzio-fastroute`
- [laminas-router](https://github.com/mezzio/mezzio-router):
  `composer require mezzio/mezzio-laminasrouter`

We recommend using a dependency injection container, and typehint against
[PSR-11 Container](https://github.com/php-fig/container). We
can recommend the following implementations:

- [laminas-servicemanager](https://github.com/laminas/laminas-servicemanager):
  `composer require laminas/laminas-servicemanager`

Additionally, you may optionally want to install a template renderer
implementation, and/or an error handling integration. These are covered in the
documentation.

## Documentation

Documentation is [in the doc tree](docs/book/), and can be compiled using [mkdocs](https://www.mkdocs.org):

```bash
mkdocs build
```

Additionally, public-facing, browseable documentation is available at
<https://docs.mezzio.dev/mezzio/>
