<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
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
    public function calculateEntity($entity)
    {
        return $entity->getCode();
    }
}
