# How can I set up the locale without routing parameters?

Localized web applications often set the locale (and therefore the language)
based on a routing parameter, the session, or a specialized sub-domain.
In this recipe we will concentrate on introspecting the URI path via middleware,
which allows you to have a global mechanism for detecting the locale without
requiring any changes to existing routes.

<!-- markdownlint-disable-next-line header-increment -->
> ### Distinguishing between routes that require localization
>
> If your application has a mixture of routes that require localization, and
> those that do not, the solution in this recipe may lead to multiple URIs
> that resolve to the identical action, which may be undesirable. In such
> cases, you may want to prefix the specific routes that require localization
> with a required routing parameter; this approach is described in the
> ["Setting a locale based on a routing parameter" recipe](setting-locale-depending-routing-parameter.md).

## Set up a middleware to extract the locale from the URI

First, we need to set up middleware that extracts the locale param directly
from the request URI's path. If it doesn't find one, it sets a default.

If it does find one, it uses the value to set up the locale. It also:

- amends the request with a truncated path (removing the locale segment).
- adds the locale segment as the base path of the `UrlHelper`.

```php
<?php

namespace App\I18n;

use Locale;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class SetLocaleMiddleware implements MiddlewareInterface
{
    private $helper;

    private $defaultLocale;
    private $fallbackLocale = 'en_US';

    const REGEX_LOCALE = '#^/(?P<locale>[a-z]{2,3}|[a-z]{2}[-_][a-zA-Z]{2})(?:/|$)#';

    public function __construct(UrlHelper $helper, string $defaultLocale = null)
    {
        $this->helper = $helper;
        if ($defaultLocale) {
            $this->defaultLocale = $defaultLocale;
        }
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $uri = $request->getUri();

        $path = $uri->getPath();

        if (! preg_match(self::REGEX_LOCALE, $path, $matches)) {
            Locale::setDefault($this->defaultLocale ?: $this->fallbackLocale);
            return $handler->handle($request);
        }

        $locale = $matches['locale'];
        Locale::setDefault(Locale::canonicalize($locale));
        $this->helper->setBasePath($locale);

        $path = substr($path, strlen($locale) + 1);

        return $handler->handle($request->withUri(
            $uri->withPath($path ?: '/')
        ));
    }
}
```

Then you will need a factory for the `SetLocaleMiddleware` to inject the
`UrlHelper` instance.

```php
<?php

namespace App\I18n;

use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

/**
 * Configuration for setting a default locale should look like the following:
 *
 * <code>
 * 'i18n' => [
 *     'default_locale' => 'de_DE',
 * ]
 * </code>
 */
class SetLocaleMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        $config = $container->has('config') ? $container->get('config') : [];
        
        return new SetLocaleMiddleware(
            $container->get(UrlHelper::class),
            $config['i18n']['default_locale'] ?? null
        );
    }
}
```

Next, map the middleware to its factory in either
`/config/autoload/dependencies.global.php` or
`/config/autoload/middleware-pipeline.global.php`:

```php
use App\I18n\SetLocaleMiddleware;
use App\I18n\SetLocaleMiddlewareFactory;

return [
    'dependencies' => [
        /* ... */
        'factories' => [
            SetLocaleMiddleware::class => SetLocaleMiddlewareFactory::class,
            /* ... */
        ],
    ],
];
```

Finally, you will need to configure your middleware pipeline to ensure this
middleware is executed on every request.

Pipe the middleware early in your application, before routing is performed:

```php
use App\I18n\SetLocaleMiddleware;

/* ... */
$app->pipe(SetLocaleMiddleware::class);
/* ... */
$app->pipe(RouteMiddleware::class);
/* ... */
$app->pipe(DispatchMiddleware::class);
/* ... */
```

## Url generation in the view

Since the `UrlHelper` has the locale set as a base path, you don't need
to worry about generating URLs within your view. Just use the helper to
generate a URL and it will do the rest.

```php
<?= $this->url('your-route') ?>
```

> ### Helpers differ between template renderers
>
> The above example is specific to laminas-view; syntax will differ for
> Twig and Plates.

## Redirecting within your request handlers

If you want to add the locale parameter when creating URIs within your
request handlers, you just need to inject the `UrlHelper` into your
handler and use it for URL generation:

```php
<?php

namespace App\Handler;

use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

class RedirectHandler implements RequestHandlerInterface
{
    private $helper;

    public function __construct(UrlHelper $helper)
    {
        $this->helper = $helper;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        $routeParams = [ /* ... */ ];

        return new RedirectResponse(
            $this->helper->generate('your-route', $routeParams)
        );
    }
}
```

Injecting the `UrlHelper` into your request handler will also require that the
handler have a factory that manages the injection. As an example, the following
would work for the above middleware:

```php
namespace App\Handler;

use Psr\Container\ContainerInterface;
use Mezzio\Helper\UrlHelper;

class RedirectHandlerFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new RedirectHandler(
            $container->get(UrlHelper::class)
        );
    }
}
```
