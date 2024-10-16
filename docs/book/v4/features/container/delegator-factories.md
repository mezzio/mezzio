# Delegator Factories

Mezzio supports the concept of _delegator factories_, which allow decoration
of services created by your dependency injection container, across all
dependency injection containers supported by Mezzio.

_Delegator factories_ accept the following arguments:

- The container itself;
- The name of the service whose creation is being decorated;
- A callback that will produce the service being decorated.

As an example, let's say we have a `UserRepository` class that composes some sort of
event manager. We might want to attach listeners to that event manager, but not
wish to alter the basic creation logic for the repository itself. As such, we
might write a _delegator factory_ as follows:

```php
namespace Acme;

use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;

class UserRepositoryListenerDelegatorFactory
{
    public function __invoke(ContainerInterface $container, string $name, callable $callback) : UserRepository
    {
        $listener = new LoggerListener($container->get(LoggerInterface::class));
        $repository = $callback();
        $repository->getEventManager()->attach($listener);
        return $repository;
    }
}
```

To notify the container about this delegator factory, we would add the following
configuration to our application:

```php
'dependencies' => [
    'delegators' => [
        Acme\UserRepository::class => [
            Acme\UserRepositoryListenerDelegatorFactory::class,
        ],
    ],
],
```

Note that you specify delegator factories using the service name being decorated
as the key, with an _array_ of delegator factories as a value. **You may attach
multiple delegator factories to any given service**, which can be a very
powerful feature.

At the time of writing, this feature works for each of the Aura.Di, Pimple, and
laminas-servicemanager container implementations.
