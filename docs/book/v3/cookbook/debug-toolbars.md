# How can I get a debug toolbar for my Mezzio application?

Many modern frameworks and applications provide debug toolbars: in-browser
toolbars to provide profiling information of the request executed. These can
provide invaluable details into application objects, database queries, and more.
As an Mezzio user, how can you get similar functionality?

## Zend Server Z-Ray

[Zend Server](https://www.zend.com/products/zend-server) ships with a tool
called [Z-Ray](https://www.zend.com/en/products/server/z-ray), which provides
both a debug toolbar and debug console (for API debugging). Z-Ray is also
currently [available as a standalone technology
preview](https://www.zend.com/en/products/z-ray/z-ray-preview), and can be added
as an extension to an existing PHP installation.

When using Zend Server or the standalone Z-Ray, you do not need to make any
changes to your application whatsoever to benefit from it; you simply need to
make sure Z-Ray is enabled and/or that you've setup a security token to
selectively enable it on-demand. See the
[Z-Ray documentation](http://files.zend.com/help/Zend-Server/content/z-ray_concept.htm)
for full usage details.

## php-middleware/php-debug-bar

[php-middleware/php-debug-bar](https://github.com/php-middleware/phpdebugbar)
provides a PSR-15 middleware wrapper around [maximebf/php-debugbar](https://github.com/maximebf/php-debugbar),
a popular framework-agnostic debug bar for PHP projects.

First, install the middleware in your application:

```bash
$ composer require php-middleware/php-debug-bar
```

This package supplies a config provider, which could be added to your
`config/config.php` when using laminas-config-aggregator or
mezzio-config-manager. However, because it should only be enabled in
development, we recommend creating a "local" configuration file (e.g.,
`config/autoload/php-debugbar.local.php`) when you need to enable it, with the
following contents:

```php
<?php

use PhpMiddleware\PhpDebugBar\ConfigProvider;
use Psr\Container\ContainerInterface;

return array_merge(ConfigProvider::getConfig(), [
    'dependencies' => [
        'factories' => [
            \PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware::class => \PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory::class,
            \DebugBar\DataCollector\ConfigCollector::class => \PhpMiddleware\PhpDebugBar\ConfigCollectorFactory::class,
            \PhpMiddleware\PhpDebugBar\ConfigProvider::class => function(ContainerInterface $container) {
                return $container->get('config');
            },
            \DebugBar\DebugBar::class => \PhpMiddleware\PhpDebugBar\StandardDebugBarFactory::class,
            \DebugBar\JavascriptRenderer::class => \PhpMiddleware\PhpDebugBar\JavascriptRendererFactory::class,
        ]
    ]
]);
```

In addition, ensure these interfaces are registered as aliases in your container. For example, with Laminas Diactoros:

```php
return [
    'dependencies' => [
        'aliases' => [
            Psr\Http\Message\ResponseFactoryInterface::class => Laminas\Diactoros\ResponseFactory::class,
            Psr\Http\Message\StreamFactoryInterface::class => Laminas\Diactoros\StreamFactory::class
        ],
        ...
];
```

Finally, add the `PhpDebugBarMiddleware` class to the pipeline in `config/pipeline.php` after the `ErrorHandler` class:
```php
if (!empty($container->get('config')['debug'])) {
    $app->pipe(PhpDebugBarMiddleware::class);
}
```

## Usage in a Request Handler
Using the debug bar in a request handler, e.g `src/App/Handler/HomePageHandler.php`:

```php
namespace App\Handler;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Mezzio\Template\TemplateRendererInterface;
use Laminas\Diactoros\Response\HtmlResponse;
use DebugBar\DebugBar;

class HomePageHandler implements RequestHandlerInterface
{
    /** @var TemplateRendererInterface */
    public $template;
    
    /** @var DebugBar */
    public $debugBar;
    
    public function __construct(TemplateRendererInterface $template, DebugBar $debugBar)
    {
        $this->template = $template;
        $this->debugBar = $debugBar;
    }
    
    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $this->debugBar["messages"]->addMessage("Hello World!");
        return new HtmlResponse($this->template->render('user::home-page'));
    }
}
```

> ### Use locally!
>
> Remember to enable `PhpMiddleware\PhpDebugBar\ConfigProvider` only in your
> development environments and remove references to `DebugBar` in production!
