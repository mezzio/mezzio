# ServerUrlHelper

`Mezzio\Helper\ServerUrlHelper` provides the ability to generate a full
URI by passing only the path to the helper; it will then use that path with the
current `Psr\Http\Message\UriInterface` instance provided to it in order to
generate a fully qualified URI.

## Usage

When you have an instance, use either its `generate()` method, or call the
instance as an invokable:

```php
// Using the generate() method:
$url = $helper->generate('/foo');

// is equivalent to invocation:
$url = $helper('/foo');
```

The helper is particularly useful when used in conjunction with the
[UrlHelper](url-helper.md), as you can then create fully qualified URIs for use
with headers, API hypermedia links, etc.:

```php
$url = $serverUrl($url('resource', ['id' => 'sha1']));
```

The signature for the ServerUrlHelper `generate()` and `__invoke()` methods is:

```php
function ($path = null) : string
```

Where:

- `$path`, when provided, can be a string path to use to generate a URI.

## Creating an instance

In order to use the helper, you will need to inject it with the current
`UriInterface` from the request instance. To automate this, we provide
`Mezzio\Helper\ServerUrlMiddleware`, which composes a `ServerUrl`
instance, and, when invoked, injects it with the URI instance.

As such, you will need to register the `ServerUrlMiddleware` as pipeline
middleware, anytime before the routing middleware:

```php
use Mezzio\Helper\ServerUrlMiddleware;

// Programmatically:
$app->pipe(ServerUrlMiddleware::class);
$app->pipe(RouteMiddleware::class);
// ...
$app->pipe(DispatchMiddleware::class);
```

> ### Skeleton configures helpers
>
> If you started your project using the Mezzio skeleton package, the
> `ServerUrlHelper` and `ServerUrlMiddleware` factories are already registered
> for you, as is the `ServerUrlMiddleware` pipeline middleware.

## Using the helper in middleware

Compose the helper in your middleware (or elsewhere), and then use it to
generate URI paths:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterfacel
use Psr\Http\Server\RequestHandlerInterface;
use Mezzio\Helper\ServerUrlHelper;

class FooMiddleware implements MiddlewareInterface
{
    private $helper;

    public function __construct(ServerUrlHelper $helper)
    {
        $this->helper = $helper;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler) : ResponseInterface
    {
        $response = $handler->handle($request);

        return $response->withHeader(
            'Link',
            $this->helper->generate() . '; rel="self"'
        );
    }
}
```
