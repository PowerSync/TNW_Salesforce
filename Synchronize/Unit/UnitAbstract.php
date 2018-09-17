<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

abstract class UnitAbstract implements Synchronize\Unit\UnitInterface
{
    /**
     * @var
     */
    private $name;

    /**
     * @var array
     */
    private $dependents;

    /**
     * @var Synchronize\Group
     */
    private $group;

    /**
     * @var Synchronize\Units
     */
    private $units;

    /**
     * @var Synchronize\Cache
     */
    protected $cache;

    /**
     * @var int
     */
    private $status = self::PENDING;

    public function __construct(
        $name,
        Synchronize\Units $units,
        Synchronize\Group $group,
        array $dependents = []
    ) {
        $this->name = $name;
        $this->group = $group;
        $this->units = $units;
        $this->dependents = array_filter($dependents);

        $cacheStorage = [];
        $this->cache = new Synchronize\Cache($cacheStorage);
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function dependents()
    {
        return $this->dependents;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Process unit %1', get_class($this));
    }

    /**
     * @return Synchronize\Group
     */
    public function group()
    {
        return $this->group;
    }

    /**
     * @return Synchronize\Units
     */
    public function units()
    {
        return $this->units;
    }

    /**
     * @param $name
     * @return UnitInterface
     * @throws \OutOfBoundsException
     */
    public function unit($name)
    {
        return $this->units->get($name);
    }

    /**
     * @param null $path
     * @param array ...$objects
     * @return mixed
     * @throws \RuntimeException
     */
    public function get($path = null, ...$objects)
    {
        if (!$this->isComplete()) {
            throw new \RuntimeException(__('Unit "%1" not complete', $this->name()));
        }

        return $this->cache->get($path, ...$objects);
    }

    /**
     * @param $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return empty($this->cache[$entity]);
    }

    /**
     * @param $status
     */
    public function status($status)
    {
        $this->status = $status;
    }

    /**
     * @return bool
     */
    public function isComplete()
    {
        return $this->status === self::COMPLETE;
    }
}
