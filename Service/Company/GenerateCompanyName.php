<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Company;

use TNW\Salesforce\Api\Service\Company\GenerateCompanyNameInterface;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetCustomerAddressByType;
use TNW\Salesforce\Utils\Company;

/**
 * Generate company name service.
 */
class GenerateCompanyName implements GenerateCompanyNameInterface
{
    /** @var GetCustomerAddressByType */
    private $getCustomerAddressByType;

    /**
     * @param GetCustomerAddressByType $getCustomerAddressByType
     */
    public function __construct(
        GetCustomerAddressByType $getCustomerAddressByType
    ) {
        $this->getCustomerAddressByType = $getCustomerAddressByType;
    }

    /**
     * @inheritDoc
     */
    public function execute($customer): string
    {
        $customerCompany = trim((string)$customer->getCompany());
        if (!empty($customerCompany)) {
            $company = $customerCompany;
        } else {
            $billingAddress = $this->getCustomerAddressByType->getDefaultBillingAddress($customer);
            if ($billingAddress) {
                $company = trim((string)$billingAddress->getCompany());
            }

            if (empty($company)) {
                $shippingAddress = $this->getCustomerAddressByType->getDefaultShippingAddress($customer);
                if ($shippingAddress) {
                    $company = trim((string)$shippingAddress->getCompany());
                }
            }

            if (empty($company)) {
                $company = Company::generateCompanyByCustomer($customer);
            }
        }

        return $company;
    }
}
