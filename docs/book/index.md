<!-- markdownlint-disable-next-line MD041-->
## Features

<!-- markdownlint-disable MD033-->
<div class="features">
    <div class="row">
        <div class="col-sm-6 col-md-4 text-center">
            <img src="images/lambda.png" alt="Middleware">

            <h3>PSR-15 Middleware</h3>

            <p>
                Create <a href="https://docs.laminas.dev/laminas-stratigility/middleware/">middleware</a>
                applications, using as many layers as you want, and the architecture
                your project needs.
            </p>
        </div>

        <div class="col-sm-6 col-md-4 text-center">
            <img src="images/check.png" alt="PSR-7">

            <h3>PSR-7 HTTP Messages</h3>

            <p>
                Built to consume <a href="https://www.php-fig.org/psr/psr-7/">PSR-7</a>!
            </p>
        </div>

        <div class="col-sm-6 col-md-4 text-center">
            <img src="images/network.png" alt="Routing">

            <h3>Routing</h3>

            <p>
                Route requests to middleware using <a href="v3/features/router/intro/">the routing library of your choice</a>.
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-6 col-md-4 text-center">
            <img src="images/syringe.png" alt="Dependency Injection">

            <h3>Dependency Injection</h3>

            <p>
                Make your code flexible and robust, using the
                <a href="v3/features/container/intro/">dependency injection container of your choice</a>.
            </p>
        </div>

        <div class="col-sm-6 col-md-4 text-center">
            <img src="images/pencil.png" alt="Templating">

            <h3>Templating</h3>

            <p>
                Create <a href="v3/features/template/intro/">templated responses</a>, using
                a variety of template engines.
            </p>
        </div>

        <div class="col-sm-6 col-md-4 text-center">
            <img src="images/error.png" alt="Error Handling">

            <h3>Error Handling</h3>

            <p>
                <a href="v3/features/error-handling/">Handle errors gracefully</a>, using
                templated error pages, <a href="https://filp.github.io/whoops/">whoops</a>,
                or your own solution!
            </p>
        </div>
    </div>
</div>

<!-- markdownlint-disable-next-line MD026-->
## Get Started Now!

Installation is only a [Composer](https://getcomposer.org) command away!

<!-- markdownlint-disable-next-line MD046-->
```bash
$ composer create-project mezzio/mezzio-skeleton mezzio
```

Mezzio provides interfaces for routing and templating, letting _you_
choose what to use, and how you want to implement it.

Our unique installer allows you to select <em>your</em> choices when starting
your project!

![Mezzio Installer](images/installer.png)

[Learn more](v3/getting-started/quick-start.md)

## Applications, Simplified

Write middleware:

<!-- markdownlint-disable-next-line MD046-->
```php
$pathMiddleware = function (
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
) {
    $uri  = $request->getUri();
    $path = $uri->getPath();

    return new TextResponse('You visited ' . $path, 200, ['X-Path' => $path]);
};
```

And add it to an application:

<!-- markdownlint-disable-next-line MD046-->
```php
$app->get('/path', $pathMiddleware);
```

[Learn more](v3/features/application.md)

## Learn more

- [Features overview](v3/getting-started/features.md)
- [Quick Start](v3/getting-started/quick-start.md)

Or use the sidebar menu to navigate to the section you're interested in.
