<?php
namespace TNW\Salesforce\Observer;

class Entities
{
    /**
     * @var array
     */
    private $entityIds = [];

    /**
     * @param $entity
     */
    public function addEntity($entity)
    {
        if ($entity instanceof \Magento\Framework\Model\AbstractModel) {
            $entity = $entity->getId();
        }

        $this->entityIds[] = $entity;
    }

    /**
     * @return array
     */
    public function entityIds()
    {
        return array_unique($this->entityIds);
    }

    /**
     * @return bool
     */
    public function isEmpty()
    {
        return empty($this->entityIds);
    }

    /**
     *
     */
    public function clean()
    {
        $this->entityIds = [];
    }
}