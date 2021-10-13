<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit;

interface HashInterface
{
    /**
     * Calculate
     *
     * @param \Magento\Framework\Model\AbstractModel $entity
     * @return string
     */
    public function calculateEntity($entity): string;
}
