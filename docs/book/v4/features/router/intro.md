# Routing

One fundamental feature of mezzio is that it provides mechanisms for
implementing dynamic routing, a feature required in most modern web
applications. As an example, you may want to allow matching both a resource, as
well as individual items of that resource:

- `/books` might return a collection of books
- `/books/mezzio` might return the individual book identified by
  "mezzio".

Mezzio does not provide routing on its own; you must choose a routing
adapter that implements `Mezzio\Router\RouterInterface`. The router is
consumed by `Mezzio\Router\Middleware\RouteMiddleware` as well as
`Mezzio\Router\RouteCollector`, the latter of which is composed in the
`Application` instance. This allows you to choose the router with the
capabilities that best match your own needs, while still providing a common
abstraction for defining and aggregating routes and their related middleware.

## Retrieving matched parameters

Routing enables the ability to match dynamic path segments (or other
criteria). Typically, you will want access to the values matched. The routing
middleware injects any matched parameters as returned by the underlying router
into the request as _attributes_.

In the example above, let's assume the route was defined as `/books/:id`, where
`id` is the name of the dynamic segment. This means that in the middleware
invoked for this route, you can fetch the `id` attribute to discover what was
matched:

```php
$id = $request->getAttribute('id');
```

## Retrieving the matched route

When routing is complete, the routing middleware injects a
`Mezzio\Router\RouteResult` instance as a request attribute, using that
class name as the attribute name. The `RouteResult` instance provides you access
to the following:

- Matching status via the methods `$result->isSuccess()`,
  `$result->isFailure()`, and `$result->isMethodFailure()` (indicating a
  path-based match that did not match an allowed HTTP method).
- Allowed HTTP methods, via `$result->getAllowedMethods()` (for either success
  or failure).
- The matched `Mezzio\Router\Route` instance, via
  `$result->getMatchedRoute()` (successful matches only).
- The matched route name, via `$result->getMatchedRouteName()` (or via
  `$result->getMatchedRoute()->getName()`; successful matches only).
- Matched parameters, via `$result->getMatchedParams()` (as noted above, these
  are also each injected as discrete request attributes; successful matches only).

As an example, you could use middleware similar to the following to return a 403
response if routing was successful, but no `Authorization` header is present:

```php
use Laminas\Diactoros\Response\EmptyResponse;
use Mezzio\Router\RouteResult;
use Psr\Http\Server\RequestHandlerInterface;

function ($request, RequestHandlerInterface $handler) use ($routesRequiringAuthorization, $validator) {
    if (! ($result = $request->getAttribute(RouteResult::class, false))) {
        // No route result present; delegate to next middleware
        return $handler->handle($request);
    }

    if (! in_array($result->getMatchedRouteName(), $routesRequiringAuthorization, true)) {
        // Not a route requiring authorization
        return $handler->handle($request);
    }

    $header = $request->getHeaderLine('Authorization');
    if (! $validator($header)) {
        return new EmptyResponse(403);
    }

    return $handler->handle($request);
}
```

Note that the first step is to determine if we have a `RouteResult`; if we do
not have one, we should either delegate to the next middleware, or return some
sort of response (generally a 404). In the case of Mezzio, a later
middleware will generate the 404 response for us, so we can safely delegate.

## URI generation

Because routers have knowledge of the various paths they can match, they are
also typically used within applications to generate URIs to other application
resources. Mezzio provides this capability in the `RouterInterface`,
either delegating to the underlying router implementations or providing a
compatible implementation of its own.

At it's most basic level, you call the `generateUri()` method with a route name
and any substitutions you want to make:

```php
$uri = $router->generateUri('book', ['id' => 'mezzio']);
```

Some routers may support providing _options_ during URI generation. Starting in
mezzio-router 2.0, which ships with Mezzio starting with version
2.0, you may also pass a third argument to `generateUri()`, an array of router
options:

```php
$uri = $router->generateUri('book', ['id' => 'mezzio'], [
    'translator'  => $translator,
    'text_domain' => $currentLocale,
]);
```

## Supported implementations

Mezzio currently ships with adapters for the following routers:

- [Aura.Router](aura.md)
- [FastRoute](fast-route.md)
- [laminas-mvc Router](laminas-router.md)
