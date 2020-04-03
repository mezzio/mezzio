# Middleware Types

Mezzio allows you to compose applications out of _pipeline_ and _routed_
middleware.

**Pipeline** middleware is middleware that defines the workflow of your
application. These generally run on every execution of the application, and
include such aspects as:

- Error handling
- Locale detection
- Session setup
- Authentication and authorization

**Routed** middleware is middleware that responds only to specific URI paths and
HTTP methods. As an example, you might want middleware that only responds to
HTTP POST requests to the path `/users`.

Mezzio allows you to define middleware using any of the following:

- [http-interop/http-middleware](https://github.com/http-interop/http-middleware/tree/0.4.1)
  instances.
- Callable middleware that implements the http-interop/http-middleware signature.
- Callable "double-pass" middleware (as used in Mezzio 1.X, and supported in
  Mezzio 2.X).
- Service names resolving to one of the above middleware types.
- Middleware pipelines expressed as arrays of the above middleware types.

## http-interop/http-middleware

The http-interop/http-middleware project is the basis for the proposed
[PSR-15](https://www.php-fig.org/psr/psr-15) specification, which covers HTTP
Server Middleware that consumes [PSR-7](http://www.php-fig.org/psr/psr-7/) HTTP
messages. The project defines two interfaces, `Interop\Http\ServerMiddleware\MiddlewareInterface`
and `Interop\Http\ServerMiddleware\DelegateInterface`. Mezzio accepts
middleware that implements the `MiddlewareInterface`. As an example:

```php
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

class SomeMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, DelegateInterface $delegate)
    {
        // do something and return a response, or
        // delegate to another handler capable of
        // returning a response via:
        //
        // return $delegate->process($request);
    }
}
```

If you are using PHP 7 or above, you could also implement such middleware via an
anonymous class.

## Callable http-middleware

Sometimes you may not want to create a class for one-off middleware. As such,
Mezzio allows you to provide a PHP callable that uses the same signature as
`Interop\Http\ServerMiddleware\MiddlewareInterface`:

```php
use Interop\Http\ServerMiddleware\DelegateInterface;
use Interop\Http\ServerMiddleware\MiddlewareInterface;
use Psr\Http\Message\ServerRequestInterface;

function (ServerRequestInterface $request, DelegateInterface $delegate)
{
    // do something and return a response, or
    // delegate to another handler capable of
    // returning a response via:
    //
    // return $delegate->process($request);
}
```

One note: the `$request` argument does not require a typehint, and examples
throughout the manual will omit the typehint when demonstrating callable
middleware.

## Double-pass middleware

Mezzio 1.X was based on Stratigility 1.X, which allowed middleware with the
following signature:

```php
<?php

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

function(
    ServerRequestInterface $request,
    ResponseInterface $response,
    callable $next
) {
    // Process the request and return a response,
    // or delegate to another process to handle
    // the request via:
    //
    // return $next($request, $response);
}
```

This middleware is called "double-pass" due to the fact that it requires both
the request and response arguments.

In such middleware, no typehints are _required_, but they are _encouraged_.
Additionally, we encourage users to _never_ use the provided `$response`
argument, but instead create a concrete response to return, or manipulate the
response returned by `$next`; this prevents a number of potential error
conditions that may otherwise occur due to incomplete or mutated response state.

This middleware is still supported in Mezzio 2.X, but we encourage users to
adopt http-interop/http-middleware signatures, as we will be deprecating
double-pass middleware eventually.

## Service-based middleware

We encourage the use of a dependency injection container for providing your
middleware. As such, Mezzio also allows you to use _service names_ for both
pipeline and routed middleware. Generally, service names will be the specific
middleware class names, but can be any valid string that resolves to a service.

When Mezzio is provided a service name for middleware, it internally
decorates the middleware in a `Mezzio\Middleware\LazyLoadingMiddleware`
instance, allowing it to be loaded only when dispatched.

## Middleware pipelines

Mezzio allows any pipeline or routed middleware to be self-contained
[middleware pipelines](https://docs.laminas.dev/laminas-stratigility/api/#middleware).
To prevent the need for instantiating a `Laminas\Stratigility\MiddlewarePipe` or
`Mezzio\Application` instance when defining the pipeline, Mezzio
allows you to provide an array of middleware:

```php
// Pipeline middleware:
$app->pipe([
    FirstMiddleware::class,
    SecondMiddleware::class,
]);

// Routed middleware:
$app->get([
    FirstMiddleware::class,
    SecondMiddleware::class,
]);
```

The values in these arrays may be any valid middleware type as defined in this
chapter.
