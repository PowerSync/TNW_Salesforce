<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

/**
 * Collect
 */
class Collect extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string[]
     */
    protected $collect;

    /**
     * CollectAbstract constructor.
     * @param string $name
     * @param string[] $collect
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     */
    public function __construct(
        $name,
        array $collect,
        Synchronize\Units $units,
        Synchronize\Group $group
    ) {
        parent::__construct($name, $units, $group, $collect);
        ksort($collect, SORT_NUMERIC);
        $this->collect = $collect;
    }

    /**
     * @inheritdoc
     */
    public function description()
    {
        return __('Analyze %1 units result', implode(', ', $this->collect));
    }

    /**
     * Process
     */
    public function process()
    {
        return;
    }

    /**
     * Get
     *
     * @param string|null $path
     * @param array $objects
     * @return mixed
     * @throws \OutOfBoundsException
     */
    public function get($path = null, ...$objects)
    {
        foreach ($this->collect as $unit) {
            $cache = $this->units()->get($unit)->get($path, ...$objects);
            if (null === $cache) {
                continue;
            }

            return $cache;
        }

        return null;
    }

    /**
     * Skipped
     *
     * @param object $entity
     * @return bool
     * @throws \OutOfBoundsException
     */
    public function skipped($entity)
    {
        foreach ($this->collect as $unit) {
            if ($this->units()->get($unit)->skipped($entity)) {
                continue;
            }

            return false;
        }

        return true;
    }
}
