# How can I set up the locale without routing parameters?

Localized web applications often set the locale (and therefore the language)
based on a routing parameter, the session, or a specialized sub-domain.
In this recipe we will concentrate on introspecting the URI path via middleware,
which allows you to have a global mechanism for detecting the locale without
requiring any changes to existing routes.

<!-- markdownlint-disable-next-line heading-increment -->
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

namespace Application\I18n;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Locale;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;

class SetLocaleMiddleware implements MiddlewareInterface
{
    private $helper;

    public function __construct(UrlHelper $helper)
    {
        $this->helper = $helper;
    }

    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $uri = $request->getUri();

        $path = $uri->getPath();

        if (! preg_match('#^/(?P<locale>[a-z]{2,3}([-_][a-zA-Z]{2}|))/#', $path, $matches)) {
            Locale::setDefault('de_DE');
            return $delegate->process($request);
        }

        $locale = $matches['locale'];
        Locale::setDefault(Locale::canonicalize($locale));
        $this->helper->setBasePath($locale);

        return $delegate->process($request->withUri(
            $uri->withPath(substr($path, strlen($locale)+1))
        ));
    }
}
```

Then you will need a factory for the `SetLocaleMiddleware` to inject the
`UrlHelper` instance.

```php
<?php

namespace Application\I18n;

use Mezzio\Helper\UrlHelper;
use Psr\Container\ContainerInterface;

class SetLocaleMiddlewareFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new SetLocaleMiddleware(
            $container->get(UrlHelper::class)
        );
    }
}
```

Next, map the middleware to its factory in either
`/config/autoload/dependencies.global.php` or
`/config/autoload/middleware-pipeline.global.php`:

```php
use Application\I18n\SetLocaleMiddleware;
use Application\I18n\SetLocaleMiddlewareFactory;

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

If using a programmatic pipeline:

```php
use Application\I18n\SetLocaleMiddleware;
use Mezzio\Helper\UrlHelperMiddleware;

/* ... */
$app->pipe(SetLocaleMiddleware::class);
/* ... */
$app->pipeRoutingMiddleware();
$app->pipe(UrlHelperMiddleware::class);
$app->pipeDispatchMiddleware();
/* ... */
```

If using a configuration-driven application, update
`/config/autoload/middleware-pipeline.global.php` to add the middleware:

```php
return [
    'middleware_pipeline' => [
        [
            'middleware' => [
                Application\I18n\SetLocaleMiddleware::class,
                /* ... */
            ],
            'priority' => 1000,
        ],

        /* ... */

        'routing' => [
            'middleware' => [
                Mezzio\Container\ApplicationFactory::ROUTING_MIDDLEWARE,
                Mezzio\Helper\UrlHelperMiddleware::class,
                Mezzio\Container\ApplicationFactory::DISPATCH_MIDDLEWARE,
            ],
            'priority' => 1,
        ],

        /* ... */
    ],
];
```

## Url generation in the view

Since the `UrlHelper` has the locale set as a base path, you don't need
to worry about generating URLs within your view. Just use the helper to
generate a URL and it will do the rest.

```php
<?php echo $this->url('your-route') ?>
```

> ### Helpers differ between template renderers
>
> The above example is specific to laminas-view; syntax will differ for
> Twig and Plates.

## Redirecting within your middleware

If you want to add the locale parameter when creating URIs within your
action middleware, you just need to inject the `UrlHelper` into your
middleware and use it for URL generation:

```php
<?php

namespace Application\Action;

use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Laminas\Diactoros\Response\RedirectResponse;
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ServerRequestInterface;

class RedirectAction implements MiddlewareInterface
{
    private $helper;

    public function __construct(UrlHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * @param ServerRequestInterface $request
     * @param DelegateInterface      $delegate
     * @return RedirectResponse
     */
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        $routeParams = [ /* ... */ ];

        return new RedirectResponse(
            $this->helper->generate('your-route', $routeParams)
        );
    }
}
```

Injecting the `UrlHelper` into your middleware will also require that the
middleware have a factory that manages the injection. As an example, the
following would work for the above middleware:

```php
namespace Application\Action;

use Psr\Container\ContainerInterface;
use Mezzio\Helper\UrlHelper;

class RedirectActionFactory
{
    public function __invoke(ContainerInterface $container)
    {
        return new RedirectAction(
            $container->get(UrlHelper::class)
        );
    }
}
```
