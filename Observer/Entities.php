<?php
declare(strict_types=1);

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

        $this->entityIds[] = $entity;
    }

    /**
     * Entity Ids
     *
     * @return array
     */
    public function entityIds(): array
    {
        return array_unique($this->entityIds);
    }

    /**
     * Is Empty
     *
     * @return bool
     */
    public function isEmpty(): bool
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
