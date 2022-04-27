<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Api\Service\Company;

use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Model\Backend\Customer as BackendCustomer;
use Magento\Customer\Model\Customer;

/**
 * Generate company name service interface.
 */
interface GenerateCompanyNameInterface
{
    /**
     * Generate comapny name by customer.
     *
     * @param CustomerInterface|BackendCustomer|Customer $customer
     *
     * @return string
     */
    public function execute($customer): string;
}
