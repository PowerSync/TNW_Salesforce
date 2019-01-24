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

    /**
     * @var \TNW\Salesforce\Model\Entity\SalesforceIdStorage
     */
    private $entityObject;

    public function __construct(
        $name,
        array $entities,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject = null
    ) {
        parent::__construct($name, $units, $group);
        $this->entities = $entities;
        $this->identification = $identification;
        $this->entityObject = $entityObject;
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
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process()
    {
        $this->cache['entities'] = [];
        foreach ($this->entities as $entity) {
            $entity = $this->loadEntity($entity);

            if (null !== $this->entityObject && null !== $entity->getId()) {
                $this->entityObject->load($entity, $entity->getConfigWebsite());
            }

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
