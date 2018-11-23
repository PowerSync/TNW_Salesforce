<?php

namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

/**
 * @deprecated
 */
abstract class SaveAbstract extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var string
     */
    private $load;

    /**
     * @var IdentificationInterface
     */
    protected $identification;

    /**
     * Initialize
     * @param string $name
     * @param string $load
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param IdentificationInterface $identification
     * @param array $dependents
     */
    public function __construct(
        $name,
        $load,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        array $dependents = []
    ) {
        parent::__construct($name, $units, $group, array_merge($dependents, [$load]));
        $this->load = $load;
        $this->identification = $identification;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Updating Magento entity ...');
    }

    /**
     * @return UnitInterface
     */
    public function load()
    {
        return $this->unit($this->load);
    }

    /**
     * @return \Magento\Catalog\Model\Product[]
     * @throws \OutOfBoundsException
     */
    protected function entities()
    {
        return array_filter($this->load()->get('entities'), [$this, 'filter']);
    }

    /**
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return bool
     */
    public function filter($entity)
    {
        return true;
    }
}