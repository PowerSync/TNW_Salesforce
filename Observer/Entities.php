<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
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
        if ($entity === null) {
            return;
        }

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
