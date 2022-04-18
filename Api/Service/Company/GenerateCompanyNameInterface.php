<?php
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
