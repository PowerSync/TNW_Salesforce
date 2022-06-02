<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Utils;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Backend\Customer as BackendCustomer;
use Magento\Customer\Model\Customer;

/**
 * Company utils class.
 */
class Company
{
    /**
     * Generate company name by customer
     *
     * @param CustomerInterface|BackendCustomer|Customer $entity
     *
     * @return string
     */
    public static function generateCompanyByCustomer($entity): string
    {
        $firstName = trim((string)$entity->getFirstname());
        $lastName = trim((string)$entity->getLastname());

        return trim((string)sprintf('%s %s', $firstName, $lastName));
    }
}
