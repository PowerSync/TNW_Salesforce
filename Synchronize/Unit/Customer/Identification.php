<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer;

use TNW\Salesforce\Synchronize;

/**
 * Customer Identification
 */
class Identification implements Synchronize\Unit\IdentificationInterface
{
    /**
     * Print Entity
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @return string
     */
    public function printEntity($entity)
    {
        return sprintf('Customer, Email "%s"', $entity->getEmail());
    }
}
