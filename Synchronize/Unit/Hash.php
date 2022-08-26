<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit;

/**
 * Entity Hash
 */
class Hash implements HashInterface
{
    /**
     * Calculate
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return string
     */
    public function calculateEntity($entity)
    {
        return spl_object_hash($entity);
    }
}
