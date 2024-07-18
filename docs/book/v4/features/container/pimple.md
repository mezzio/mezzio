# Using Pimple

[Pimple](http://pimple.sensiolabs.org/) is a widely used, code-driven,
dependency injection container provided as a standalone component by SensioLabs.
It features:

- combined parameter and service storage.
- ability to define factories for specific classes.
- lazy-loading via factories.

Pimple only supports programmatic creation at this time.

## Installing and configuring Pimple

Pimple implements [PSR-11 Container](https://github.com/php-fig/container)
as of version 3.2. To use Pimple as a dependency injection container, we
recommend using [laminas/laminas-pimple-config](https://github.com/laminas/laminas-pimple-config),
which helps you to configure the PSR-11 container. First install the package:

```bash
$ composer require laminas/laminas-pimple-config
```

Now, create the file `config/container.php` with the following contents:

```php
<?php

use Laminas\Pimple\Config\Config;
use Laminas\Pimple\Config\ContainerFactory;

$config  = require __DIR__ . '/config.php';
$factory = new ContainerFactory();

return $factory(new Config($config));
```

For more information, please see the
[laminas-pimple-config documentation](https://github.com/laminas/laminas-pimple-config/blob/master/README.md).

Your bootstrap (typically `public/index.php`) will then look like this:

```php
chdir(dirname(__DIR__));
require 'vendor/autoload.php';

$container = require 'config/container.php';
$app = $container->get(Mezzio\Application::class);

require 'config/pipeline.php';
require 'config/routes.php';

$app->run();
```
