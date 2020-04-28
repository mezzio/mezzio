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

use DebugBar\DataCollector\ConfigCollector;
use DebugBar\DebugBar;
use DebugBar\JavascriptRenderer;
use PhpMiddleware\PhpDebugBar\ConfigCollectorFactory;
use PhpMiddleware\PhpDebugBar\ConfigProvider;
use PhpMiddleware\PhpDebugBar\JavascriptRendererFactory;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddleware;
use PhpMiddleware\PhpDebugBar\PhpDebugBarMiddlewareFactory;
use PhpMiddleware\PhpDebugBar\StandardDebugBarFactory;
use Psr\Container\ContainerInterface;

return array_merge(ConfigProvider::getConfig(), [
    'dependencies' => [
        'factories' => [
            PhpDebugBarMiddleware::class => PhpDebugBarMiddlewareFactory::class,
            ConfigCollector::class => ConfigCollectorFactory::class,
            ConfigProvider::class => function(ContainerInterface $container) {
                return $container->get('config');
            },
            DebugBar::class => StandardDebugBarFactory::class,
            JavascriptRenderer::class => JavascriptRendererFactory::class,
        ]
    ]
]);
```

In addition, ensure the [PSR-17 HTTP message factory interfaces](https://www.php-fig.org/psr/psr-17/)
are registered in your container. For example, when using
[Diactoros](https://docs.laminas.dev/laminas-diactoros) as your
[PSR-7 HTTP message interfaces](https://www.php-fig.org/psr/psr-7)
implementation, you can define the following:

```php
use Laminas\Diactoros\ResponseFactory;
use Laminas\Diactoros\StreamFactory;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

return [
    'dependencies' => [
        'invokables' => [
            ResponseFactoryInterface::class => ResponseFactory::class,
            StreamFactoryInterface::class   => StreamFactory::class
        ],
        // ...
];
```

> Starting with [Diactoros](https://docs.laminas.dev/laminas-diactoros) 2.3.0, you
> can register the above PSR-17 services by adding an entry for
> `\Laminas\Diactoros\ConfigProvider::class` to your `config/config.php` file,
> if it is not added for you during installation.

Finally, add the `PhpDebugBarMiddleware` class to the pipeline in
`config/pipeline.php` after piping the `ErrorHandler` class:

```php
if (! empty($container->get('config')['debug'])) {
    $app->pipe(PhpDebugBarMiddleware::class);
}
```

### Usage in a Request Handler

You can add messages to the debug bar within request handlers and middleware. As
an example, in your `src/App/Handler/HomePageHandler.php`, you might do the
following:

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
        $this->debugBar['messages']->addMessage('Hello World!');
        return new HtmlResponse($this->template->render('user::home-page'));
    }
}
```

> ### Only use in development
>
> Remember to enable `PhpMiddleware\PhpDebugBar\ConfigProvider` only in your
> development environments, and to remove references to the `DebugBar` class in
> production!
