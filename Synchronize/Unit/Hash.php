<?php
declare(strict_types=1);

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
    public function calculateEntity($entity): string
    {
        return spl_object_hash($entity);
    }
}
