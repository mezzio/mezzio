# UrlHelper

`Mezzio\Helper\UrlHelper` provides the ability to generate a URI path
based on a given route defined in the `Mezzio\Router\RouterInterface`.
If injected with a route result, and the route being used was also the one
matched during routing, you can provide a subset of routing parameters, and any
not provided will be pulled from those matched.

## Usage

When you have an instance, use either its `generate()` method, or call the
instance as an invokable:

```php
// Using the generate() method:
$url = $helper->generate('resource', ['id' => 'sha1']);

// is equivalent to invocation:
$url = $helper('resource', ['id' => 'sha1']);
```

The signature for both is:

```php
function (
    $routeName,
    array $routeParams = [],
    $queryParams = [],
    $fragmentIdentifier = null,
    array $options = []
) : string
```

Where:

- `$routeName` is the name of a route defined in the composed router. You may
  omit this argument if you want to generate the path for the currently matched
  request.
- `$routeParams` is an array of substitutions to use for the provided route, with the
  following behavior:
    - If a `RouteResult` is composed in the helper, and the `$routeName` matches
      it, the provided `$params` will be merged with any matched parameters, with
      those provided taking precedence.
    - If a `RouteResult` is not composed, or if the composed result does not match
      the provided `$routeName`, then only the `$params` provided will be used
      for substitutions.
    - If no `$params` are provided, and the `$routeName` matches the currently
      matched route, then any matched parameters found will be used.
      parameters found will be used.
    - If no `$params` are provided, and the `$routeName` does not match the
      currently matched route, or if no route result is present, then no
      substitutions will be made.
- `$queryParams` is an array of query string arguments to include in the
  generated URI.
- `$fragmentIdentifier` is a string to use as the URI fragment.
- `$options` is an array of options to provide to the router for purposes of
  controlling URI generation. As an example, laminas-router can consume "translator"
  and "text_domain" options in order to provide translated URIs.

Each method will raise an exception if:

- No `$routeName` is provided, and no `RouteResult` is composed.
- No `$routeName` is provided, a `RouteResult` is composed, but that result
  represents a matching failure.
- The given `$routeName` is not defined in the router.

> ### Signature changes
>
> The signature listed above is current as of version 3.0.0 of
> mezzio/mezzio-helpers. Prior to that version, the helper only
> accepted the route name and route parameters.

### Reusing Matched Route Result Parameters

When you're on a route that has many parameters, often times it makes sense to reuse
currently matched route parameters instead of assigning them explicitly, this is the 
default behaviour.

As an example, we will imagine being on a detail page for our `blog` route. We want
to display links to the `edit` and `delete` actions without having to assign the ID
again:

```php
// Current URL: /blog/view/777

$this->url('blog', ['action' => 'edit']);
$this->url('blog', ['action' => 'delete']);
```

The `UrlHelper` will generate the route: `/blog/edit/777` and `/blog/delete/777` 
respectively. 

However, this may not always be desired, if we are on the detail page for our `blog`
route, and wanted to generate a canonical url to our blog, we can pass the 
`reuse_result_params` option with  value of `false` to prevent reusing route parameters: 

```php
// Generated url: /blog/list?results_per_page=10 

$this->url('blog', ['action' => 'list'], ['results_per_page' => 10], null, ['reuse_result_params' => false]);
``` 

### Reusing Query Parameters

There may be times when it would be convenient to reuse query parameters for the matched
route. For example, referring back to our blog scenario with the generated route:
`/blog/list?results_per_page=10`. We would like to add a link to the next page of
results, while retaining the query parameters (results_per_page). 

We can achieve this by passing the `reuse_query_params` option to the `UrlHelper`as follows:

```php
// Generated url: /blog/list?results_per_page=10&page=2

echo $this->url('blog', ['action' => 'list'], ['page' => 2], null, ['reuse_query_params' => true]);
```

### Other methods available

- `getRouteResult() : ?Mezzio\Router\RouteResult` (since
  mezzio-helpers 5.2.0): if you want access to the result of routing —
  and, consequently, the matched route name, matched route parameters, and
  matched route — you can use this method. The method returns `null` if no route
  result has been injected yet — which typically happens in the
  `UrlHelperMiddleware`, discussed in the next section.

  As an example:

  ```php
  $templateParams = [];
  $routeResult    = $this->urlHelper->getRouteResult();
  if ($routeResult->isSuccess()) {
      $templateParams['route']        = $routeResult->getMatchedRouteName();
      $templateParams['route_params'] = $routeResult->getMatchedParams();
  }
  ```


### Registering the pipeline middleware

For the `UrlHelper` to work, you must first register the `UrlHelperMiddleware`
as pipeline middleware following the routing middleware, and before the dispatch
middleware:

```php
use Mezzio\Helper\UrlHelperMiddleware;

// Programmatically:
$app->pipe(RouteMiddleware::class);
// ...
$app->pipe(UrlHelperMiddleware::class);
$app->pipe(DispatchMiddleware::class);
```

> #### Skeleton configures helpers
>
> If you started your project using the Mezzio skeleton package, the
> `UrlHelper` and `UrlHelperMiddleware` factories are already registered for
> you, as is the `UrlHelperMiddleware` pipeline middleware.

## Using the helper in middleware

Compose the helper in your middleware (or elsewhere), and then use it to
generate URI paths:

```php
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class FooMiddleware implements MiddlewareInterface
{
    private $helper;

    public function __construct(UrlHelper $helper)
    {
        $this->helper = $helper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $response = $handler->handle($request);
        return $response->withHeader(
            'Link',
            $this->helper->generate('resource', ['id' => 'sha1'])
        );
    }
}
```

## Base Path support

If your application is running under a subdirectory, or if you are running
pipeline middleware that is intercepting on a subpath, the paths generated
by the router may not reflect the *base path*, and thus be invalid. To
accommodate this, the `UrlHelper` supports injection of the base path; when
present, it will be prepended to the path generated by the router.

As an example, perhaps you have middleware running to intercept a language
prefix in the URL; this middleware could then inject the `UrlHelper` with the
detected language, before stripping it off the request URI instance to pass on
to the router:

```php
use Mezzio\Helper\UrlHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class LocaleMiddleware implements MiddlewareInterface
{
    private $helper;

    public function __construct(UrlHelper $helper)
    {
        $this->helper = $helper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $uri = $request->getUri();
        $path = $uri->getPath();
        if (! preg_match('#^/(?P<locale>[a-z]{2,3}([-_][a-zA-Z]{2}|))/#', $path, $matches)) {
            return $handler->handle($request);
        }

        $locale = $matches['locale'];
        Locale::setDefault(Locale::canonicalize($locale));
        $this->helper->setBasePath($locale);

        return $handler->handle($request->withUri(
            $uri->withPath(substr($path, strlen($locale) + 1))
        ));
    }
}
```

(Note: if the base path injected is not prefixed with `/`, the helper will add
the slash.)

Paths generated by the `UriHelper` from this point forward will have the
detected language prefix.

## Router-specific helpers

- Since mezzio-router 3.1.0 and mezzio-helpers 5.1.0.

Occasionally, you may want to provide a different router instance to nested
pipeline middleware; in particular, this may occur when you want to [segregate a
pipeline by path](../router/piping.md#path-segregation).

In such situations, you cannot reuse the `UrlHelper` instance, as a different
router is in play; additionally, it may need to define a base path so that any
generated URIs contain the full path information (since path segregation strips
the specified path prefix from the request).

To facilitate such scenarios, the factories for the `UrlHelper` and
`UrlHelperMiddleware` allow providing optional arguments to allow varying
behavior:

- `UrlHelperFactory` allows passing an alternate router service name.
- `UrlHelperMiddlewareFactory` allows passing an alternate URL helper service name.

As an example, let us consider a module named `Auth` where we want to define a
path-segregated middleware pipeline that has its own router and route
middleware. We might define its dependency configuration as follows:

```php
namespace Auth;

use Mezzio\Helper\UrlHelperFactory;
use Mezzio\Helper\UrlHelperMiddlewareFactory;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Middleware\RouteMiddlewareFactory;

return [
    'dependencies' => [
        'factories' => [
            // module-specific class name => factory
            Router::class                 => FastRouteRouterFactory::class,
            RouteMiddleware::class        => new RouteMiddlewareFactory(Router::class),
            UrlHelper::class              => new UrlHelperFactory('/auth', Router::class),
            UrlHelperMiddleware::class    => new UrlHelperMiddlewareFactory(UrlHelper::class),
        ],
    ],
];
```

We could then create a path-segregated pipeline like the following:

```php
$app->pipe('/auth', [
    \Auth\RouteMiddleware::class,     // module-specific routing middleware!
    ImplicitHeadMiddleware::class,
    ImplicitOptionsMiddleware::class,
    MethodNotAllowedMiddleware::class,
    \Auth\UrlHelperMiddleware::class, // module-specific URL helper middleware!
    DispatchMiddleware::class,
]);
```

Any handlers that the module-specific router routes to can then also compose the
same `UrlHelper` instance via their factories:

```php
namespace Auth;

use Psr\Container\ContainerInterface;

class SomeHandlerFactory
{
    public function __invoke(ContainerInterface $container) : SomeHandler
    {
        return new SomeHandler(
            $container->get(UrlHelper::class) // module-specific URL helper!
        );
    }
}
```

This instance will then be properly configured to generate links using the
module-specific router.
