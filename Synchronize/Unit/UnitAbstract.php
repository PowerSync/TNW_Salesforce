<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

use Magento\Framework\Phrase;
use OutOfBoundsException;
use RuntimeException;
use TNW\Salesforce\Synchronize;

/**
 * UnitAbstract
 */
abstract class UnitAbstract implements Synchronize\Unit\UnitInterface
{
    const MIN_LEN_SF_ID = 15;

    /**
     * @var
     */
    protected $name;

    /**
     * @var array
     */
    protected $dependents;

    /**
     * @var Synchronize\Group
     */
    protected $group;

    /**
     * @var Synchronize\Units
     */
    protected $units;

    /**
     * @var Synchronize\Cache
     */
    protected $cache;

    /**
     * @var int
     */
    private $status = self::PENDING;

    /**
     * @var int
     */
    private $origStatus = self::PENDING;

    /**
     * UnitAbstract constructor.
     * @param string $name
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param array $dependents
     */
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
     * Name
     *
     * @return string
     */
    public function name(): string
    {
        return $this->name;
    }

    /**
     * Dependents
     *
     * @return array
     */
    public function dependents(): array
    {
        return $this->dependents;
    }

    /**
     * @inheritdoc
     */
    public function description(): Phrase
    {
        return __('Process unit %1', get_class($this));
    }

    /**
     * Group
     *
     * @return Synchronize\Group
     */
    public function group(): Synchronize\Group
    {
        return $this->group;
    }

    /**
     * Units
     *
     * @return Synchronize\Units
     */
    public function units(): Synchronize\Units
    {
        return $this->units;
    }

    /**
     * Unit
     *
     * @param string $name
     * @return UnitInterface|null
     * @throws OutOfBoundsException
     */
    public function unit($name): ?UnitInterface
    {
        return $this->units->get($name);
    }

    /**
     * @param $entity
     * @return array
     */
    public function getAllEntityError($entity): array
    {
        $errors = [];

        /**
         * @var string $key
        * @var UnitAbstract $unit
        */
        foreach ($this->units() as $key => $unit) {
            if (!$unit->isComplete()) {
                continue;
            }

            $errors[] = $unit->get('error/%s', $entity);
        }

        /** remove empty items */
        return array_filter($errors);
    }

    /**
     * Get
     *
     * @param string|null $path
     * @param array ...$objects
     * @return mixed
     * @throws RuntimeException
     */
    public function get($path = null, ...$objects)
    {
        if (!$this->isComplete()) {
            throw new RuntimeException(__('Unit "%1" not complete', $this->name()));
        }

        return $this->cache->get($path, ...$objects);
    }

    /**
     * Skipped
     *
     * @param object $entity
     * @return bool
     */
    public function skipped($entity): bool
    {
        return empty($this->cache[$entity]);
    }

    /**
     * Status
     *
     * @param string $status
     */
    public function status($status)
    {
        $this->status = $status;
    }

    /**
     * @return void
     */
    public function forceStatus($status)
    {
        $this->origStatus = $this->status;
        $this->status($status);
    }

    /**
     * @return void
     */
    public function restoreStatus()
    {
        $this->status($this->origStatus);
    }

    /**
     * Is Complete
     *
     * @return bool
     */
    public function isComplete(): bool
    {
        return $this->status === self::COMPLETE;
    }

    /**
     * @param $id
     * @return bool|string
     */
    public function correctSalesforceId($id)
    {
        return is_string($id) ? substr($id, 0, self::MIN_LEN_SF_ID) : $id;
    }
}
