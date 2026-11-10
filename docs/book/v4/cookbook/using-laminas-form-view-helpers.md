# How can I use laminas-form view helpers?

If you've selected laminas-view as your preferred template renderer, you'll likely
want to use the various view helpers available in other components, such as:

- laminas-form
- laminas-i18n
- laminas-navigation

By default, only the view helpers directly available in laminas-view are available;
how can you add the others?

## ConfigProvider

When you install laminas-form, Composer should prompt you if you want to inject one
or more `ConfigProvider` classes, including those from laminas-hydrator,
laminas-inputfilter, and several others. Always answer "yes" to these; when you do,
a Composer plugin will add entries for their `ConfigProvider` classes to your
`config/config.php` file.

If for some reason you are not prompted, or chose "no" when answering the
prompts, you can add them manually. Add the following entries in the array used
to create your `ConfigAggregator` instance within `config/config.php`:

```php
    \Laminas\Form\ConfigProvider::class,
    \Laminas\InputFilter\ConfigProvider::class,
    \Laminas\Filter\ConfigProvider::class,
    \Laminas\Validator\ConfigProvider::class,
    \Laminas\Hydrator\ConfigProvider::class,
```

If you installed Mezzio via the skeleton, the service
`Laminas\View\HelperPluginManager` is registered for you, and represents the helper
plugin manager injected into the `PhpRenderer` instance. This instance gets its
helper configuration from the `view_helpers` top-level configuration key &mdash;
which the laminas-form `ConfigProvider` helps to populate!

At this point, all view helpers provided by laminas-form are registered and ready
to use.
