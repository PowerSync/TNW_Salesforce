<?php
declare(strict_types=1);

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
    public function printEntity($entity): string
    {
        return sprintf('Customer, Email "%s"', $entity->getEmail());
    }
}
