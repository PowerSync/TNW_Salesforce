<?php
namespace TNW\Salesforce\Synchronize\Unit\Customer\Mapping\Loader;

/**
 * Mapping Loader Customer
 */
class Customer extends \TNW\Salesforce\Synchronize\Unit\EntityLoaderAbstract
{
    /**
     * Load
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @return \Magento\Framework\Model\AbstractModel
     */
    public function load($entity)
    {
        return $entity;
    }
}
