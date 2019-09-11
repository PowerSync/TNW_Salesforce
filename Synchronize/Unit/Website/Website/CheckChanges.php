<?php

namespace TNW\Salesforce\Synchronize\Unit\Website\Website;

use Magento\Framework\Model\AbstractModel;
use OutOfBoundsException;
use TNW\Salesforce\Synchronize;
use TNW\Salesforce\Synchronize\Unit\Load;
use TNW\Salesforce\Synchronize\Unit\LookupAbstract;
use TNW\Salesforce\Synchronize\Unit\UnitInterface;

/**
 * Lookup
 *
 */
class CheckChanges extends Synchronize\Unit\UnitAbstract
{
    /** @var string */
    protected $load;

    /** @var string */
    protected $lookup;

    /** @var string */
    protected $mapping;

    /**
     * @var array
     */
    protected $compareFields = [
        'Id',
        'tnw_mage_basic__Code__c',
        'Name',
        'tnw_mage_basic__Website_ID__c',
    ];

    /**
     * CheckChanges constructor.
     *
     * @param $name
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param $load
     * @param $lookup
     * @param $mapping
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param array $dependents
     */
    public function __construct(
        string $name,
        $load,
        $lookup,
        $mapping,
        Synchronize\Units $units,
        Synchronize\Group $group,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, $dependents);

        $this->load = $load;
        $this->lookup = $lookup;
        $this->mapping = $mapping;
    }

    /**
     * Entities
     *
     * @return AbstractModel[]
     * @throws OutOfBoundsException
     */
    protected function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * Lookup
     *
     * @return LookupAbstract|UnitInterface
     */
    public function lookup()
    {
        return $this->unit($this->lookup);
    }

    /**
     * Lookup
     *
     * @return Synchronize\Unit\Mapping|UnitInterface
     */
    public function mapping()
    {
        return $this->unit($this->mapping);
    }

    /**
     * Load
     *
     * @return Load|UnitInterface
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * Filter
     *
     * @param AbstractModel $entity
     * @return bool
     * @throws OutOfBoundsException
     */
    protected function filter($entity)
    {
        return !in_array(true, array_map(function ($unit) use ($entity) {
            return $this->unit($unit)->skipped($entity);
        }, $this->dependents()), true);
    }

    /**
     * Check if the Salesforce org contains actual information already
     */
    public function process()
    {
        foreach ($this->entities() as $entity) {
            $mappedObject = $this->mapping()->get('%s', $entity);
            $lookupObject = $this->lookup()->get('%s/record', $entity);

            foreach ($this->compareFields as $compareField) {
                if ($mappedObject[$compareField] != $lookupObject[$compareField]) {
                    return true;
                }
            }

            $this->cache[$entity] = null;
//            $this->mapping()->cache get('%s', $entity) = null;
            // MARK entity as successed
        }
    }
}
