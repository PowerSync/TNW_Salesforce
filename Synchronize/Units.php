<?php
namespace TNW\Salesforce\Synchronize;

/**
 * Units
 */
class Units implements \IteratorAggregate
{
    protected $units = [];

    /**
     * Add
     *
     * @param Unit\UnitInterface|null $unit
     * @return $this
     */
    public function add(Unit\UnitInterface $unit)
    {
        if ($unit) {
            $this->units[$unit->name()] = $unit;
        }

        return $this;
    }

    /**
     * Get
     *
     * @param string $name
     * @return Unit\UnitInterface
     * @throws \OutOfBoundsException
     */
    public function get($name)
    {
        if (empty($this->units[$name])) {
            return null;
//            throw new \OutOfBoundsException(__('Unit name "%1" not fount', $name));
        }

        return $this->units[$name];
    }

    /**
     * Sort
     *
     * @return $this
     * @throws \RuntimeException
     */
    public function sort()
    {
        $addUnit = function (array &$sortUnits, Unit\UnitInterface $unit) use (&$addUnit) {
            foreach ($unit->dependents() as $dependent) {
                if (empty($this->units[$dependent])) {
                    throw new \RuntimeException(sprintf('Not found unit "%s"', $dependent));
                }

                if (isset($sortUnits[$dependent])) {
                    continue;
                }

                $addUnit($sortUnits, $this->units[$dependent]);
            }

            $sortUnits[$unit->name()] = $unit;
        };

        $sortUnits = [];
        foreach ($this->units as $unit) {
            $addUnit($sortUnits, $unit);
        }

        $this->units = $sortUnits;
        return $this;
    }

    /**
     * Retrieve an external iterator
     *
     * @return \Traversable
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->units);
    }
}
