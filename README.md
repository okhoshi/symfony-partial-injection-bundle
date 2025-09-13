# Partial injection for Symfony Service Container

This bundle provides the building blocks to register and inject services that can receive some of their dependencies
from the container through injection (and even auto-wiring), while the caller provides the rest at invocation.

## How to use?

Enable the bundle in your app configuration, and that's it! You can start decorating your services right away.

To configure a service as partially injected, add the `#[AsPartial]` attribute on the class, and mark each argument
you want to provide at creation time with `#[Partial]` attribute, or as arguments of the `#[AsPartial]` attribute.

```php
#[AsPartial]
final readonly class PartiallyInjectedService {

    public function __construct(
        private ClockInterface $clock,
        #[Partial]
        private NotInjectableService $myService,
        #[Partial]
        private string $oneStringValue,
    ) {
    }
}
```

Then, you can get a `PartiallyInjectedService` instance thanks to the `#[AutowireCallable]` attribute.

```php
final readonly class UsePartialService {

    /**
     * @param Closure(NonInjectableService $myService, string $oneStringValue): PartiallyInjectedService $partiallyInjectedService
     */
    public function __construct(
        #[AutowireCallable(service: PartiallyInjectedService)]
        private \Closure $partiallyInjectedService,
    ) {
    }

    public function someFunction(): void
    {
        /** @var NotInjectableService $svc */
        $instance = ($this->partiallyInjectedService)(myService: $svc, oneStringValue: 'foo');

        assert($instance instanceof PartiallyInjectedService);
    }
}
```

> Important: you **must** provide named arguments to the closure, positional arguments will not work.

## Alternative setup

Sometimes, decorating a class is not possible. Like if it's an abstract class and you want to apply this behavior to all
its subclasses. In that situation, you can use tag the service when you register it in the container, with
`AsPartial::COMPILER_PASS_TAG`. The list of lazy arguments can then be provided with an attribute named
`AsPartial::LAZY_ARGS_TAG_ATTRIBUTE` on that tag.

```php
/** @var ContainerBuilder $container */
$container->registerForAutoconfiguration(AbstractClass::class)
    ->addTag(AsPartial::COMPILER_PASS_TAG, [
        AsPartial::LAZY_ARGS_TAG_ATTRIBUTE => ['value'],
    ]);
```

> Note: the compiler pass will make check every subclass of `AbstractClass` have a matching `$value` argument in its
> constructor.

## Incompatibilities

Partial services cannot have interface aliases. As they are not concrete classes in the container, Symfony cannot check
they implement the interface represented by the alias.

To avoid errors, the compiler pass thus removes any interface alias on services decorated with `#[AsPartial]`.
