<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Website;

use TNW\Salesforce\Synchronize;

/**
 * Website Hash
 */
class Hash implements Synchronize\Unit\HashInterface
{
    /**
     * Calculate
     *
     * @param \Magento\Store\Model\Website $entity
     * @return string
     */
    public function calculateEntity($entity): string
    {
        return $entity->getCode();
    }
}
