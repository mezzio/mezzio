# How can I tell my application about a base path?

In some environments, your application may be running in a subdirectory of your
web root. For example:

```text
var/
|- www/
|  |- wordpress/
|  |- mezzio/
|  |  |- public/
|  |  |  |- index.php
```

where `/var/www` is the web root, and your Mezzio application is in the
`mezzio/` subdirectory. How can you make your application work correctly in
this environment?

## Configure Web Server

### Using Apache

If you are using Apache, your first step is to add an `.htaccess` file to your
application root, with directives for rewriting to the `public/` directory:

```ApacheConf
RewriteEngine On
RewriteRule (.*) ./public/$1
```

### Using Nginx

If you are using Nginx, add a named location block to rewrite to the Mezzio web root directory within the server context.

```nginx
server {
    root /var/www/;

    location / {
        try_files $uri /index.php @mezzio;
    }

    location @mezzio {
        rewrite /(.*)$ /mezzio/public/index.php?/$1 last;
    }

    location ~ \.php {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;

        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;

        internal;
    }
}
```

More detail is provided on this in the [official Nginx documentation](https://nginx.org/en/docs/http/ngx_http_core_module.html#location).

> ### Using other web servers
>
> If you are using a web-server other than Apache or Nginx, and know how to do a similar
> rewrite, we'd love to know! Please submit ideas/instructions to
> [our issue tracker](https://github.com/mezzio/mezzio/issues)!

## Use middleware to rewrite the path

The above step ensures that clients can hit the website. Now we need to ensure
that the application can route to middleware!

To do this, we will add pipeline middleware to intercept the request, and
rewrite the URL accordingly.

At the time of writing, we have two suggestions:

- [los/basepath](https://github.com/Lansoweb/basepath) provides the basic
  mechanics of rewriting the URL.
- [mtymek/blast-base-url](https://github.com/mtymek/blast-base-url) provides the
  URL rewriting mechanics, as well as utilities for generating URIs that retain
  the base path.

### los/basepath

To use `los/basepath`, install it via Composer, copy the configuration files to
your application, and then edit the configuration.

To install and copy the configuration:

```bash
$ composer require los/basepath
$ cp vendor/los/basepath/config/los-basepath.global.php.dist config/autoload/los-basepath.global.php
```

We recommend copying the global configuration to a local configuration file as
well; this allows you to have the production settings in your global
configuration, and development settings in a local configuration (which is
excluded from git by default):

```bash
$ cp config/autoload/los-basepath.global.php config/autoload/los-basepath.local.php
```

Then edit one or both, to change the `los_basepath` settings:

```php
return [
    'los_basepath' => '<base path here>',
    /* ... */
];
```

The base path should be the portion of the web root leading up to the
`index.php` of your application. In the above example, this would be
`/mezzio`.

### mtymek/blast-base-url

To use `mtymek/blast-base-url`, install it via Composer, and register some
configuration.

To install it:

```bash
$ composer require mtymek/blast-base-url
```

If you are using `laminas-component-installer`, you will be prompted to configure this package automatically.
If you don't choose to do so, you please refer to `Blast\BaseUrl\ConfigProvider` class to see how to wire it manually.

If using programmatic pipelines, pipe the middleware early in your pipeline (before routing):

```php
$app->pipe(\Blast\BaseUrl\BaseUrlMiddleware::class);
```

For configuration-driven pipelines, add an entry in your
`config/autoload/middleware-pipeline.global.php` file:

```php
'middleware_pipeline' => [
    ['middleware' => [Blast\BaseUrl\BaseUrlMiddleware::class], 'priority' => 1000],
    /* ... */
],
```

At this point, the middleware will take care of the rewriting for you. No
configuration is necessary, as it does auto-detection of the base path based on
the request URI and the operating system path to the application.

The primary advantage of `mtymek/blast-base-url` is in its additional features:

- it injects `Mezzio\Helper\UrlHelper` with the base path, allowing you
  to create relative route-based URLs.
- it provides a new helper, `Blast\BaseUrl\BasePathHelper`, which allows you to
  create URLs relative to the base path; this is particularly useful for assets.
  If you are using laminas-view as your templating engine, this view helper will
  be available out of the box.

Usage of the `BasePath` helper is as follows:

```php
// where $basePathHelper is an instance of Blast\BaseUrl\BasePathHelper
// as pulled from your container:
echo $basePathHelper('/icons/favicon.ico');

// or, from laminas-view's PhpRenderer:
echo $this->basePath('/icons/favicon.ico');
```
