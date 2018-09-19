<?php
namespace TNW\Salesforce\Synchronize\Unit;

use TNW\Salesforce\Synchronize;

abstract class LoadAbstract extends Synchronize\Unit\UnitAbstract
{
    /**
     * @var array
     */
    protected $entities;

    /**
     * @var IdentificationInterface
     */
    protected $identification;

    public function __construct(
        $name,
        array $entities,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification
    ) {
        parent::__construct($name, $units, $group);
        $this->entities = $entities;
        $this->identification = $identification;
    }

    /**
     * @return IdentificationInterface
     */
    public function identification()
    {
        return $this->identification;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Loading Magento entities ...');
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $this->cache['entities'] = [];
        foreach ($this->entities as $entity) {
            $entity = $this->loadEntity($entity);
            $this->cache['entities'][$entity] = $entity;
            $message[] = __('Entity %1 loaded', $this->identification->printEntity($entity));
        }

        if (!empty($message)) {
            $this->group()->messageDebug(implode("\n", $message));
        }
    }

    /**
     * @param mixed $entity
     * @return \Magento\Framework\Model\AbstractModel
     */
    abstract public function loadEntity($entity);

    /**
     * @return array
     */
    public function entities()
    {
        return $this->cache->get('entities');
    }

    /**
     * @param $entity
     * @return bool
     */
    public function skipped($entity)
    {
        return empty($this->cache['entities'][$entity]);
    }
}