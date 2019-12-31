# Using Plates

[Plates](https://github.com/thephpleague/plates) is a native PHP template system
maintained by [The League of Extraordinary Packages](http://thephpleague.com).
it provides:

- Layout facilities.
- Template inheritance.
- Helpers for escaping, and the ability to provide custom helper extensions.

We provide a [TemplateInterface](interface.md) wrapper for Plates via
`Mezzio\Template\Plates`.

## Installing Plates

To use the Plates wrapper, you must first install Plates:

```bash
$ composer require league/plates
```

## Using the wrapper

If instantiated without arguments, `Mezzio\Template\Plates` will create
an instance of the Plates engine, which it will then proxy to.

```php
use Mezzio\Template\Plates;

$templates = new Plates();
```

Alternately, you can instantiate and configure the engine yourself, and pass it
to the `Mezzio\Template\Plates` constructor:

```php
use League\Plates\Engine as PlatesEngine;
use Mezzio\Template\Plates;

// Create the engine instance:
$plates = new PlatesEngine();

// Configure it:
$plates->addFolder('error', 'templates/error/');
$plates->loadExtension(new CustomExtension();

// Inject:
$templates = new Plates($plates);
```
