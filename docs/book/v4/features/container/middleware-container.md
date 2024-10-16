# The Middleware Container

While the skeleton creates a general
[PSR-11](https://www.php-fig.org/psr/psr-11/) container in which to map all of
your dependencies, this can pose problems when you are attempting to pull
middleware and request handlers; you could potentially, accidentally, pull
something of a different type entirely, which may not work in either context!

To prevent this from happening, we provide
`Mezzio\MiddlewareContainer`. It decorates your application container,
and adds the following behavior:

- `has()` will return `true` if a service does not exist in the container, but
  is a class that exists.
- `get()`:
    - will instantiate a class directly if the service does not exist, but is a
      class that exists.
    - decorate PSR-15 `RequestHandlerInterface` implementations using
      `Laminas\Stratigility\RequestHandlerMiddleware`.
    - raise an exception if the instance to return is not a PSR-15
      `MiddlewareInterface` implementation.

Internally, this class is used by the [MiddlewareFactory](middleware-factory.md)
and the `Mezzio\Middleware\LazyLoadingMiddleware` class; you should
never need to interact with it directly, unless the above features are of
interest to you.
