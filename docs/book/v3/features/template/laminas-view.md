# Using laminas-view

[laminas-view](https://docs.laminas.dev/laminas-view/) provides a native PHP
template system via its `PhpRenderer`, and is maintained by Laminas. It
provides:

- Layout facilities.
- Helpers for escaping, and the ability to provide custom helper extensions.

We provide a [TemplateRendererInterface](interface.md) wrapper for laminas-view's
`PhpRenderer` via `Mezzio\LaminasView\LaminasViewRenderer`.

## Installing laminas-view

To use the laminas-view wrapper, you must first install the laminas-view integration:

```bash
$ composer require mezzio/mezzio-laminasviewrenderer
```

## Available Versions

The following table shows the compatibility of the libraries that go together to provide a usable templating system using `laminas-view`:

| `laminas-view` | `mezzio-template` | `mezzio-laminasviewrenderer` |
|----------------|-------------------|------------------------------|
| 2.x            | 2.x               | 2.x                          |
| 3.x            | 3.x               | 3.x                          |

There are significant changes in `laminas-view` version 3, but broadly speaking, its usage in a Mezzio application is largely unchanged.

This documentation will cover usage of `laminas-view` with the version 3 series of releases.

You can read the integration documentation for `mezzio-laminasviewrenderer` version 2 in the [older documentation for Mezzio](../../../v2/features/template/laminas-view.md).

## Using the wrapper

By default, once the components are installed, retrieving the `Mezzio\Template\TemplateRendererInterface` from the application DI container will yield an instance of `Mezzio\LaminasView\LaminasViewRenderer`.

From a Mezzio perspective, rendering templates in request handlers or middleware is no different to any other `mezzio-template` implementation:

```php
use Laminas\Diactoros\Response\HtmlResponse;
use Mezzio\Template\TemplateRendererInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

final readonly class ExampleRequestHandler implements RequestHandlerInterface
{
    public function __construct(
        private TemplateRendererInterface $renderer
    ) {
    }
    
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        return new HtmlResponse(
            $this->renderer->render('some-template', ['some' => 'data']),
            200,
        );
    }
}
```

In order to instantiate the template renderer yourself, you will need to retrieve a number of dependencies from the container:

```php
use Psr\Container\ContainerInterface;
use Laminas\View\HelperPluginManagerInterface;
use Laminas\View\Renderer\RendererInterface;
use Mezzio\LaminasView\LaminasViewRenderer;

assert($container instanceof ContainerInterface);

$defaultLayoutTemplate = 'some::layout';

$renderer = new LaminasViewRenderer(
    $container->get(RendererInterface::class),
    $container->get(HelperPluginManagerInterface::class),
    $defaultLayoutTemplate,
);
```

### Template Resolvers

The Laminas template renderer (`Laminas\View\Renderer\PhpRenderer`) consumes [template resolvers](https://docs.laminas.dev/laminas-view/v3/template-resolvers/) in order to map template names to file paths.

`laminas-view` itself ships several template resolvers, and `mezzio-laminasviewrenderer` ships an additional resolver called `Mezzio\LaminasView\NamespacedPathStackResolver`.

Conceptually, template resolvers are straight-forward; given a template name, return the path to an on-disk template file or return false.

In Mezzio, when you ask for `Laminas\View\Resolver\ResolverInterface`, you will receive an "AggregateResolver", that configures a "MapResolver" followed by the "NamespacedPathStackResolver".

What this means is that the "MapResolver" will be consulted first _(The fastest resolver)_ and the "NamespacedPathStackResolver" second, when the "MapResolver" fails to identify the template asked for.

If we were to configure the aggregate resolver ourselves, it would look like this:

```php
use Laminas\View\Resolver\AggregateResolver;
use Laminas\View\Resolver\TemplateMapResolver;
use Mezzio\LaminasView\NamespacedPathStackResolver;

$mappedTemplates = [
    'one' => __DIR__ . '/templates/one.phtml',
    'two' => __DIR__ . '/templates/two.phtml',
];

$namespacedPaths = [
    'muppets' => __DIR__ . '/templates/muppets', // A directory containing multiple files
];

// The "Map Resolver"
$mapResolver = new TemplateMapResolver($mappedTemplates);

// The "Namespaced Path Stack Resolver"
$namespacedResolver = new NamespacedPathStackResolver([
    'script_paths' => $namespacedPaths,
    // ... Other Options
]);

/**
 * Now we can create the aggregate resolver: 
 */
$aggregateResolver = new AggregateResolver([
    $mapResolver,
    $namespacedResolver,
]);

/**
 * And locate template files: 
 */
$one = $aggregateResolver->resolve('one'); // templates/one.phtml
$kermit = $aggregateResolver->resolve('muppets::kermit'); // templates/muppets/kermit.phtml
```

The configuration above essentially describes the default setup you will get.

All you need to do is configure the templates and retrieve the template resolver from the container:

```php
// config/autoload/templates.global.php
return [
    'templates' => [
        'default_suffix' => 'phtml',
        'map' => [
            'one' => __DIR__ . '/templates/one.phtml',
            'two' => __DIR__ . '/templates/two.phtml',
        ],
        'paths' => [
            'muppets' => __DIR__ . '/templates/muppets',
        ],
    ],
];
```

```php
use Laminas\View\Resolver\ResolverInterface;
use Psr\Container\ContainerInterface;

assert($container instanceof ContainerInterface);

$resolver = $container->get(ResolverInterface::class);

$resolver->resolve('one'); // templates/one.phtml
$resolver->resolve('muppets::kermit'); // templates/muppets/kermit.phtml
$resolver->resolve('not-there'); // false
```

We encourage you to familiarise yourself with the [template resolver documentation](https://docs.laminas.dev/laminas-view/v3/template-resolvers/) in `laminas-view` for alternative ways of resolving templates and how to implement your own template resolver.

> NOTE: **Namespaced Path Resolving in Mezzio**
> The Mezzio-specific `Mezzio\LaminasView\NamespacedPathStackResolver` satisfies the convention of "namespaced" template paths.
> The format is `namespace::template` and typically, `namespace` refers to one or more directories and `template` refers to a file named `template.phtml` in one of those directories.

### View Helpers _(Plugins)_

All templating systems provide a way of authoring plugins or helpers to make repetitive, or complex rendering tasks easy - helping to keep templates easy to maintain or perform essential functions such as escaping output.
`laminas-view` is no different, and it comes with a number of ready-to-use helpers which are [documented in detail over on the Laminas website](https://docs.laminas.dev/laminas-view/v3/helpers/intro/).

Creating your own plugins is straight-forward, and we encourage you to again [look at the relevant documentation](https://docs.laminas.dev/laminas-view/v3/helpers/advanced-usage/) in order to do this.

`mezzio-laminasviewrenderer` comes with a few Mezzio-specific plugins:

#### The `serverUrl` Plugin

This plugin proxies to the [ServerUrlHelper](../helpers/server-url-helper.md) to return absolute URIs:

```php
// some-template.phtml

echo $this->serverUrl('/path.html'); // 'https://example.com/path.phtml'
```

#### The `url` Plugin

This plugin proxies to the [UrlHelper](../helpers/url-helper.md) to assemble local URLs for your configured routes:

```php
// some-template.phtml

// '/some-path/page/1#some-hash?search=foo'
echo $this->url('my-route', ['page' => 1], ['search' => 'foo'], 'some-hash');
```

### Layouts

To take advantage of layouts in Mezzio with `laminas-view`, configure the _default_ layout template appropriately:

```php
// config/autoload/templates.global.php

return [
    'templates' => [
        'layout' => 'layout::default', // Default layout template
        'map' => [
            
            'layout::default' => __DIR__ . '/templates/layout/default-layout.phtml',
            'layout::alternative' => __DIR__ . '/templates/layout/alternative-layout.phtml',
            
            'page::home' => __DIR__ . '/templates/static/home.phtml',
            'page::other' => __DIR__ . '/templates/static/other.phtml',
        ],
    ],
];
```

With the above configuration, calling `render('page::home')` on the template renderer will yield the contents of `home.phtml` inside of `default-layout.phtml`.

#### Changing Layout

Layout can be changed from the default, or set when no default has been configured in several ways:

- Passing a `layout` variable during rendering of a template:
  ```php
  $renderer->render('page::home', ['layout' => 'layout::alternative']);
  ```
- Calling `addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'layout', 'layout::alternative')` on the template renderer.
- Calling the `layout` view-helper from within a template:
  ```php
  // other.phtml
  $this->layout('layout::alternative');
  // more template content…
  ```
- Calling the `layout` view-helper in other contexts such as middleware

#### Disabling Layout

You can disable layouts entirely by not configuring a default layout _(This is default behaviour)_, or in one of the following ways:

- Passing a `layout` variable with a `false` value during rendering:
  ```php
  $renderer->render('page::home', ['layout' => false]);
  ```
- Calling `addDefaultParam(TemplateRendererInterface::TEMPLATE_ALL, 'layout', false)` on the template renderer.
- Calling the `layout` view-helper with an empty string from within a template:
  ```php
  // other.phtml
  $this->layout('');
  // more template content…
  ```
- Calling the `layout` view-helper in other contexts such as middleware.

When layout is enabled, the laminas-view implementation will do a depth-first, recursive
render in order to provide content within the selected layout.

Additionally, layouts, when passed as parameters during rendering can also be `Laminas\View\Model\ViewModel` instances:

#### Provide a layout view model when rendering

```php
use Laminas\View\Model\ViewModel;

// Create the layout view model:
$layout = new ViewModel([
    'cssPath'  => '/css/blog/',
]);
$layout->setTemplate('layout::blog-layout');

$content = $renderer->render('blog::entry', [
    'layout' => $layout,
    'entry'  => $blogEntryDataModel,
]);
```

### Templates

Template files for consumption in `laminas-view` are of course specific to `laminas-view` and are incompatible with either the [Plates template engine](./plates.md) or the [Twig template engine](./twig.md).

For more information on writing templates, please [consult the Laminas View documentation](https://docs.laminas.dev/laminas-view/v3/view-scripts/).

## Recommendations

We recommend the following practices when using the `laminas-view` adapter:

- If using a layout, create a factory to return the layout view model as a
  service; this allows you to inject it into middleware and add variables to it.
- While we support passing the layout as a rendering parameter, be aware that if
  you change engines, this may not be supported.
- If you need to make use of multiple layout templates, carefully consider which method you use to change layout and stick to it.
  Sometimes, it makes most sense to delegate layout selection to the content template, in other situations manipulating layouts in middleware is the right thing to do.
  Mixing methods however, is not advised, and will lead to confusion in future!
- Leverage custom plugins _("View Helpers")_ to make testable and maintainable utilities that help you write consistent markup.
- Investigate the [Partial View Helper](https://docs.laminas.dev/laminas-view/v3/helpers/partial/) for re-using chunks of markup in dedicated templates.

Laminas View is also capable of rendering arbitrarily nested view models, which can be of significant help when working with complex layouts.  
