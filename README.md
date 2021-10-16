# Dynamic Registry

Create registries that are filled on-demand instead of wasting memory when they are never accessed.

## Example

### Extending

Extend `AbstractRegistry` for each type of registry you want to keep a set of values for.

```php
class ItemsRegistry extends AbstractRegistry
{
    public function getName() : string
    {
        return 'items';
    }
}

class EntitiesRegistry extends AbstractRegistry
{
    protected $defaultValues = [1, 2, 3]; // Optional.

    public function getName() : string
    {
        return 'entities';
    }
}
```

Or if you only want strings in the registry, you can extend `AbstractStringRegistry`. When a non-string value is added to such a registry, `InvalidValue` is thrown.


```php
class ItemsRegistry extends AbstractStringRegistry
{
    public function getName() : string
    {
        return 'items';
    }
}
```

Or if you want a simple container and have no use for multiple unique registries, create a new `SimpleRegistry` object.

```php
$registry = new SimpleRegistry(-1, -2, -3); // Optionally add values upon instantiation.
```

### Usage

You can either use `new` to create a new object for your registry or obtain a singleton via `get()`.

```php
$itemsRegistry = ItemsRegistry::get();
$entitiesRegistry = EntitiesRegistry::get(4, 5, 6); // Optionally add values upon FIRST get() call.
```

Apart from those initially-present values, you can create and register a dynamic populator. A dynamic populator adds values to the registry the first time that the registry has been queried via `has()` or if the registry has been forcibly populated with `populate()`.

```php
class ItemsPopulator implements DynamicPopulatorInterface
{
    public function populate(AbstractRegistry $registry) : void
    {
        $registry->addValues('a', 'b', 'c');
    }
}
```

Of course, rather than dynamically populating with hard-coded values, you would instead populate the registry through some other means, such as from a database or via an external API. After registering the populator, once `has()` is called, the registry will be automatically populated and will return true if the value exists.

```php
$itemsRegistry->has('a'); // false
$itemsRegistry->register(new ItemsPopulator());
$itemsRegistry->has('a'); // true
```

----

Be warned that attempting to add multiple of the same value will result in `InvalidValue` being thrown. To avoid this consequence and instead cause it to fail silently, override the `failSilently` method.

```php
class ItemsRegistry extends AbstractStringRegistry
{
    public function getName() : string
    {
        return 'items';
    }
    
    public function failSilently(): bool
    {
        return true;
    }
}
```