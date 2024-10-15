# Overview

Mezzio allows you to write [PSR-15](http://www.php-fig.org/psr/psr-15/)
middleware applications for the web.

PSR-15 consumes [PSR-7](http://www.php-fig.org/psr/psr-7/) HTTP Message
Interfaces; these are the incoming request and outgoing response for your
application. By using both PSR-15 and PSR-7, we ensure that your applications
will work in other contexts that are compatible with these standards.

Middleware is any code sitting between a request and a response; it typically
analyzes the request to aggregate incoming data, delegates it to another layer
to process, and then creates and returns a response. Middleware can and should
be relegated only to those tasks, and should be relatively easy to write and
maintain.

PSR-15 also defines _request handlers_; these are classes that receive a
request and return a response, without delegating to other layers of the
application. These are generally the innermost layers of your application.

Middleware is also designed for composability; you should be able to nest
middleware and re-use middleware.

With Mezzio, you can build middleware applications such as the following:

- APIs
- Websites
- Single Page Applications
- and more.

## Features

Mezzio builds on [laminas-stratigility](https://docs.laminas.dev/laminas-stratigility/) (Stratigility) to provide a robust convenience layer on which to build applications.
The features it provides include:

### Powerful Routing

Stratigility provides the foundation for Mezzio's routing.
But, Stratigility only provides limited, literal route matching via `PathMiddlewareDecorator`.
Mezzio builds on this, providing an abstracted routing layer that allows the developer to choose the routing library that best fits the project needs.

And, among other features, it:

- Supports dynamic routing capabilities from a variety of router packages, including [FastRoute][fastroute-url], and [laminas-router][laminas-router-url]
- Provides much more fine-grained matching capabilities than Stratigility does
- It allows restricting matched routes to specific HTTP methods and returns [405 Not Allowed][405-not-allowed-url] responses with an [Allow HTTP header][allow-http-header-url] containing the allowed HTTP methods for invalid requests

### A PSR-11 Container

Mezzio encourages the use of dependency injection, and defines its core `Application` class to compose a [PSR-11][psr11-url] `ContainerInterface` instance.
The container is used to lazy-load middleware, whether it is piped (Stratigility interface) or routed (Mezzio).

### Flexible Templating

While Mezzio does not assume templating is being used, it provides a templating abstraction layer, allowing developers to choose the templating package that best suits their needs.
In addition, developers can write middleware that typehints on this abstraction, and assume that the underlying templating package will provide layout support and namespaced template support.
By default, Mezzio provides wrappers for [Plates][plates-url], and [Twig][twig-url], and [laminas-view][laminas-view-url].

### Error Handling

Applications should handle errors gracefully, but also handle them differently in development versus production.
Mezzio provides both basic error handling via Stratigility's own `ErrorHandler` implementation, providing specialized error response generators that can perform templating, and integrates with [Whoops][whoops-url].

## Flow Overview

Below is a diagram detailing the workflow used by Mezzio.

![Mezzio Architectural Flow](../../images/architecture.png)

The `Application` acts as an "onion"; in the diagram above, the top is the
outermost layer of the onion, while the bottom is the innermost.

The `Application` dispatches each middleware. Each middleware receives a request
and a delegate for handing off processing of the request should the middleware
not be able to fully process it itself. Internally, the delegate composes a
queue of middleware, and invokes the next in the queue when invoked.

Any given middleware can return a _response_, at which point execution winds
its way back out the onion.

> ### Pipelines
>
> The terminology "pipeline" is often used to describe the onion. One way of
> looking at the "onion" is as a _queue_, which is first-in-first-out (FIFO) in
> operation. This means that the first middleware on the queue is executed first,
> and this invokes the next, and so on (and hence the "next" terminology). When
> looked at from this perspective:
>
> - In most cases, the entire queue _will not_ be traversed.
> - The innermost layer of the onion represents the last item in the queue, and
>   should be guaranteed to return a response; usually this is indicative of
>   a malformed request (HTTP 400 response status) and/or inability to route
>   the middleware to a handler (HTTP 404 response status).
> - Responses are returned _through_ the pipeline, in reverse order of
>   traversal.

> ### Double pass middleware
>
> The system described above is what is known as _lambda middleware_. Each
> middleware receives the request and a handler, and you pass only the
> request to the handler when wanting to hand off processing:
>
> ```php
> function (ServerRequestInterface $request, RequestHandlerInterface $handler)
> {
>     $response = $handler->handle($request);
>     return $response->withHeader('X-Test', time());
> }
> ```
>
> In Mezzio 1.X, the default middleware style was what is known as _double
> pass_ middleware. Double pass middleware receives both the request and a
> response in addition to the handler, and passes both the request and response
> to the handler when invoking it:
>
> ```php
> function (ServerRequestInterface $request, ResponseInterface $response, callable $next)
> {
>     $response = $next($request, $response);
>     return $response->withHeader('X-Test', time());
> }
> ```
>
> It is termed "double pass" because you pass both the request and response when
> delegating to the next layer.
>
> Mezzio 3 no longer supports double-pass middleware directly. However, if
> you decorate it using `Laminas\Stratigility\doublePassMiddleware()`, we can
> consume it. That function requires first the double-pass middleware, and then
> a response prototype (which will be passed as the `$response` argument to the
> middleware):
>
> ```php
> use function Laminas\Stratigility\doublePassMiddleware;
>
> $app->pipe(doublePassMiddleware(function ($request, $response, $next) {
>     // ...
> }, new Response()));
> ```
>
> If you use double-pass middleware, _do not_ use the `$response` instance
> passed to it unless you are returning it specifically (e.g., because you are not
> delegating to another layer).

The `Application` allows arbitrary middleware to be injected, with each being
executed in the order in which they are attached; returning a response from
middleware prevents any middleware attached later from executing.

The middleware pipeline is executed in the order of attachment.

Mezzio provides default implementations of "routing" and "dispatch"
middleware, which you will attach to the middleware pipeline.  These are
implemented as the classes `Mezzio\Router\Middleware\RouteMiddleware`
and `Mezzio\Router\Middleware\DispatchMiddleware`, respectively.

Routing within Mezzio consists of decomposing the request to match it to
middleware that can handle that given request. This typically consists of a
combination of matching the requested URI path along with allowed HTTP methods:

- map a GET request to the path `/api/ping` to the `PingMiddleware`
- map a POST request to the path `/contact/process` to the `HandleContactMiddleware`
- etc.

Dispatching is simply the act of calling the middleware mapped by routing. The
two events are modeled as separate middleware to allow you to act on the results
of routing before attempting to dispatch the mapped middleware; this can be
useful for implementing route-based authentication or validation, or, as we
provide by default, handling `HEAD` and `OPTIONS` requests, or providing `405
Method Not Allowed` responses.

The majority of your application will consist of routing rules that map to
routed middleware and request handlers.

Middleware piped to the application earlier than routing should be middleware
that you wish to execute for every request. These might include:

- bootstrapping
- parsing of request body parameters
- addition of debugging tools
- embedded middleware pipelines/application that you want to match at a given
  literal path
- etc.

Such middleware may decide that a request is invalid, and return a response;
doing so means no further middleware will be executed! This is an important
feature of middleware architectures, as it allows you to define
application-specific workflows optimized for performance, security, etc.

Middleware piped to the application after the routing and dispatch middleware
will execute in one of two conditions:

- routing failed
- routed middleware called on the next middleware instead of returning a response.

As such, the largest use case for such middleware is to provide a "default"
error response for your application, usually as an HTTP 404 Not Found response.

The main points to remember are:

- The application is a queue, and operates in FIFO order.
- Each middleware can choose whether to return a response, which will cause
  the queue to unwind, or to traverse to the next middleware.
- Most of the time, you will be defining _routed middleware_, and the routing
  rules that map to them.
- _You_ get to control the workflow of your application by deciding the order in
  which middleware is queued.

[405-not-allowed-url]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Status/405
[allow-http-header-url]: https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Allow
[laminas-view-url]: https://docs.laminas.dev/laminas-view/
[plates-url]: https://platesphp.com/
[fastroute-url]: https://github.com/nikic/FastRoute
[laminas-router-url]: https://docs.laminas.dev/laminas-router/routing/
[psr11-url]: https://www.php-fig.org/psr/psr-11
[twig-url]: https://twig.symfony.com/
[whoops-url]: http://filp.github.io/whoops/
