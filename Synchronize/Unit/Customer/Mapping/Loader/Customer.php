<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer\Mapping\Loader;

use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Synchronize\Unit\EntityLoaderAbstract;

/**
 * Mapping Loader Customer
 */
class Customer extends EntityLoaderAbstract
{
    /**
     * Load
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @return AbstractModel
     */
    public function load($entity)
    {
        return $entity;
    }
}
