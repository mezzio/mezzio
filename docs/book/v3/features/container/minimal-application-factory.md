# Minimal Application Factory

The `MinimalApplicationFactory` allows you to create a Mezzio Application
instance in shorter way by assuming defaults for most dependencies. Using
this method of instantiating the application assumes:

 - You have `laminas/diactoros` installed
 - You do not need the `SapiStreamEmitter`
 - Your `ServerRequest` comes from `$_SERVER`

All you need to provide is:

 - A container instance implementing `ContainerInterface`, for example
   [laminas-servicemanager](https://docs.laminas.dev/laminas-servicemanager/)
 - A router instance implementing `RouterInterface`, for example [FastRoute](https://docs.mezzio.dev/mezzio/v3/features/router/fast-route/)
 
You would then pipe the `RouteMiddleware`, `DispatchMiddleware`, and any other
middleware or request handlers that your application needs. 
 
## A Hello World application using `MinimalApplicationFactory`

First you must require Laminas Diactoros, a container, and a router with
Composer. In this example, we'll use Laminas ServiceManager and FastRoute.

```bash
$ composer require laminas/laminas-servicemanager laminas/laminas-diactoros mezzio/mezzio-fastroute
```

Then in `public/index.php` we can create our application:

```php
<?php

use Laminas\Diactoros\Response\TextResponse;
use Laminas\ServiceManager\ServiceManager;
use Mezzio\Container\MinimalApplicationFactory;
use Mezzio\Router\FastRouteRouter;
use Mezzio\Router\Middleware\DispatchMiddleware;
use Mezzio\Router\Middleware\RouteMiddleware;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;

$container = new ServiceManager();
$router = new FastRouteRouter();
$app = MinimalApplicationFactory::create($container, $router);
$app->pipe(new RouteMiddleware($router));
$app->pipe(new DispatchMiddleware());
$app->get('/hello-world', new class implements RequestHandlerInterface {
    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        return new TextResponse('Hello world!');
    }
});
$app->run();
```

You can use the PHP built-in web server to check this works. Spin this up with:

```bash
$ php -S 0.0.0.0:8080 -t public public/index.php
```

Now if you visit http://localhost:8080 in your browser, you should see the text
`Hello world!` displayed. Now you're ready to make middleware in minutes!
