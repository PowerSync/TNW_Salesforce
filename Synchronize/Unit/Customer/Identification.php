<?php
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
