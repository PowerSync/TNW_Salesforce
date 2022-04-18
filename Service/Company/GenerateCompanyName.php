<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\Company;

use TNW\Salesforce\Api\Service\Company\GenerateCompanyNameInterface;
use TNW\Salesforce\Utils\Company;

/**
 * Generate company name service.
 */
class GenerateCompanyName implements GenerateCompanyNameInterface
{
    /**
     * @inheritDoc
     */
    public function execute($customer): string
    {
        $customerCompany = trim((string)$customer->getCompany());
        $billingAddress = $customer->getDefaultBillingAddress();
        $shippingAddress = $customer->getDefaultShippingAddress();
        switch (true) {
            case (!empty($customerCompany)):
                $company = $customerCompany;
                break;

            case ($billingAddress && !empty(trim((string)$billingAddress->getCompany()))):
                $company = trim((string)$billingAddress->getCompany());
                break;

            case ($shippingAddress && !empty(trim((string)$shippingAddress->getCompany()))):
                $company = trim((string)$shippingAddress->getCompany());
                break;

            default:
                $company = Company::generateCompanyByCustomer($customer);
                break;
        }

        return $company;
    }
}
