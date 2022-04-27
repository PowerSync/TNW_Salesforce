<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Website;

use TNW\Salesforce\Synchronize;

/**
 * Identification
 */
class Identification implements Synchronize\Unit\IdentificationInterface
{
    /**
     * Print Entity
     *
     * @param \Magento\Store\Model\Website $entity
     * @return string
     */
    public function printEntity($entity)
    {
        return sprintf('website (code: %s)', $entity->getCode());
    }
}
