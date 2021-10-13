<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Mapping\Loader;

use Magento\Framework\DataObject;
use Magento\Framework\Model\AbstractModel;

/**
 * Mapping Loader Customer
 */
class Customer extends \TNW\Salesforce\Synchronize\Unit\EntityLoaderAbstract
{
    /**
     * Load
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @return DataObject
     */
    public function load($entity): DataObject
    {
        return $entity;
    }
}
