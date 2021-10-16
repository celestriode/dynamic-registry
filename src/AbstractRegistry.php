<?php namespace Celestriode\DynamicRegistry;

use Celestriode\DynamicRegistry\Exception\InvalidValue;

/**
 * A registry contains a list of values to be used with various audits that rely on them. For example, if a string input
 * must be one from a list of valid Minecraft blocks from 1.17, then you could use a registry containing those values.
 *
 * Rather than having a registry for every version or manually populating each type of registry, you can use a dynamic
 * registry populator. See DynamicPopulatorInterface for more details. Dynamic populating is what saves on memory: if a
 * registry is never accessed, then its values are never populated. Once a registry is accessed, it will retain the data
 * until it is manually cleared or program execution ends.
 *
 * Create a registry class that extends AbstractRegistry. Then you can call YourClass::get() to get a singleton of that
 * registry, which may or may not already be populated depending on if it was accessed beforehand. "Access" in this case
 * means having called the "has()" method, which will run the "populate()" method. You can also run the "populate()"
 * method yourself if you have some future expectation of a registry requiring population.
 *
 * @package Celestriode\DynamicRegistry
 */
abstract class AbstractRegistry
{
    /**
     * @var AbstractRegistry[] Instances of registries.
     */
    private static $instances = [];

    /**
     * @var DynamicPopulatorInterface[] A list of DynamicPopulatorInterface objects, which are used for population.
     */
    private $populators = [];

    /**
     * @var array An optional list of default values that will be part of the registry. When reset() is called, the
     * values listed here will be filled back into the registry.
     */
    protected $defaultValues = [];

    /**
     * @var array The list of values associated with this registry, added manually through a variety of methods.
     */
    private $values = [];

    /**
     * @var array The list of values associated with this registry, added through dynamic population via populate().
     */
    private $dynamicValues = [];

    /**
     * @var bool Whether or not $values has been populated dynamically.
     */
    private $populated = false;

    /**
     * @var bool State variable indicating whether or not the registry is currently populating.
     */
    private $populating = false;

    /**
     * Returns a friendly name of the registry.
     *
     * @return string
     */
    abstract public function getName(): string;

    /**
     * Optionally instantiates with a list of values.
     *
     * @param mixed ...$values The values to add to the registry.
     * @throws InvalidValue
     */
    public function __construct(...$values)
    {
        if (!empty($values)) {

            $this->setValues(...$values);
        }
    }

    /**
     * Returns whether or not the list of values has been populated via the populate() method.
     *
     * @return bool
     */
    public function populated(): bool
    {
        return $this->populated;
    }

    /**
     * Sets the registry as being populated (or not). A registry is populated only when populate() is called. A registry
     * is unpopulated if depopulate() is called. Or you can manipulate it yourself.
     *
     * @param bool $populated
     * @return $this
     */
    public function setPopulated(bool $populated = true): self
    {
        $this->populated = $populated;

        return $this;
    }

    /**
     * Resets the registry and then adds the given values to it.
     *
     * @param mixed ...$values The values to add to the registry.
     * @return AbstractRegistry
     * @throws InvalidValue
     */
    public function setValues(...$values): self
    {
        if ($this->populating) {

            $this->dynamicValues = [];
        } else {

            $this->values = [];
        }

        $this->addValues(...$values);

        return $this;
    }

    /**
     * Adds multiple values to the registry. Any duplicates will be ignored.
     *
     * @param mixed ...$values The values to add to the registry.
     * @return $this
     * @throws InvalidValue
     */
    public function addValues(...$values): self
    {
        foreach ($values as $value) {

            $this->addValue($value);
        }

        return $this;
    }

    /**
     * Adds a value to the registry provided that it is valid.
     *
     * @param mixed $value The value to add to the registry.
     * @return $this
     * @throws InvalidValue
     */
    public function addValue($value): self
    {
        if (!$this->validValue($value)) {

            if ($this->failSilently()) {

                return $this;
            }

            throw new InvalidValue('Value is not allowed within registry.');
        }

        if ($this->populating) {

            $this->dynamicValues[] = $value;
        } else {

            $this->values[] = $value;
        }

        return $this;
    }

    /**
     * Returns whether or not the input value is valid for adding to the registry.
     *
     * The only condition by default is that the value must not already exist in the registry.
     *
     * @param $value
     * @return bool
     */
    protected function validValue($value): bool
    {
        if (in_array($value, $this->getValues())) {

            return false;
        }

        return true;
    }

    /**
     * Returns whether or not the registry should throw InvalidValue when isValid returns true.
     *
     * @return bool
     */
    protected function failSilently(): bool
    {
        return false;
    }

    /**
     * Returns all the values of the registry. If the registry has no default values and hasn't been populated in some
     * form, the array will be empty. Call populate() first or check populated().
     *
     * @return array
     */
    public function getValues(): array
    {
        return array_merge($this->defaultValues, $this->values, $this->dynamicValues);
    }

    /**
     * Returns whether or not the given value exists within the registry. Attempts to populate the registry if it wasn't
     * already populated.
     *
     * Make sure you populate registries using the RegistryPopulator before calling this method.
     *
     * @param $value
     * @return bool
     */
    public function has($value): bool
    {
        // Dynamically populate the registry before attempting a match, if it wasn't already populated.
        // Return whether or not the input is within the populated registry.

        return in_array($value, $this->populate()->getValues());
    }

    /**
     * Populates the registry from dynamic populators if the registry wasn't already populated.
     *
     * @return $this
     */
    final public function populate(): self
    {
        if (!$this->populated() && !$this->populating) {

            $this->populateRegistryDynamically();

            // Mark the registry as having been populated.

            $this->setPopulated();
        }

        return $this;
    }

    /**
     * Empties out the registry and un-marks it as populated. This means that future accesses will re-populate it.
     *
     * Note that this only removes values that were added through dynamic population. If you want to remove all values
     * entirely, use reset().
     *
     * @return $this
     */
    public function depopulate(): self
    {
        $this->dynamicValues = [];
        $this->setPopulated(false);

        return $this;
    }

    /**
     * Empties out all values, including those added through dynamic population and those added manually. Use the
     * clearPopulators() method alongside this if you also want to remove dynamic populators.
     *
     * @return $this
     */
    public function reset(): self
    {
        $this->values = [];
        $this->dynamicValues = [];
        $this->setPopulated(false);

        return $this;
    }

    /**
     * Creates a singleton of the registry class.
     *
     * @return static
     * @throws InvalidValue
     */
    final public static function get(...$values): self
    {
        // Obtain the singleton.

        $class = self::$instances[static::class] ?? new static(...$values);

        // Store the class if it wasn't already stored.

        if (!isset(self::$instances[static::class])) {

            self::$instances[static::class] = $class;
        }

        // All set, return the class.

        return $class;
    }

    /**
     * Registers a dynamic populator with this instance.
     *
     * @param DynamicPopulatorInterface $populator
     * @return AbstractRegistry
     */
    final public function register(DynamicPopulatorInterface $populator): self
    {
        $this->populators[] = $populator;

        return $this;
    }

    /**
     * Returns the populators that have been registered with this instance.
     *
     * @return DynamicPopulatorInterface[]
     */
    final public function getPopulators(): array
    {
        return $this->populators;
    }

    /**
     * Removes all populators associated with this instance.
     *
     * @return AbstractRegistry
     */
    public function clearPopulators(): self
    {
        $this->populators = [];

        return $this;
    }

    /**
     * Uses the dynamic populators stored with this registry to populate the registry on-demand. When populating, all
     * method calls that add a value to the registry will instead add those values to a separate dynamic list of values.
     * Because of this, you can use any of the methods that add values without conflicting.
     */
    final private function populateRegistryDynamically(): void
    {
        $this->populating = true;

        foreach ($this->populators as $populator) {

            $populator->populate($this);
        }

        $this->populating = false;
    }
}