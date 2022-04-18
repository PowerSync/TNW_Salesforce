<?php
namespace TNW\Salesforce\Observer;

/**
 * Entities Storage
 */
class Entities
{
    /**
     * @var array
     */
    private $entityIds = [];

    /**
     * Add Entity
     *
     * @param int|\Magento\Framework\Model\AbstractModel $entity
     */
    public function addEntity($entity)
    {
        if ($entity instanceof \Magento\Framework\Model\AbstractModel) {
            $entity = $entity->getId();
        }

        $this->entityIds[] = (int)$entity;
        $this->entityIds = array_unique($this->entityIds);
    }

    /**
     * Entity Ids
     *
     * @return array
     */
    public function entityIds()
    {
        return array_unique($this->entityIds);
    }

    /**
     * Is Empty
     *
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->entityIds);
    }

    /**
     * Clean
     */
    public function clean()
    {
        $this->entityIds = [];
    }
}
