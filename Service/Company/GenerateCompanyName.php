<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Company;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\Service\Company\GenerateCompanyNameInterface;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetCustomerAddressesByAddressIds;
use TNW\Salesforce\Utils\Company;

/**
 * Generate company name service.
 */
class GenerateCompanyName implements GenerateCompanyNameInterface
{
    /** @var GetCustomerAddressesByAddressIds */
    private $getCustomerAddressesByAddressIds;

    /**
     * @param GetCustomerAddressesByAddressIds $getCustomerAddressesByAddressIds
     */
    public function __construct(
        GetCustomerAddressesByAddressIds $getCustomerAddressesByAddressIds
    ) {
        $this->getCustomerAddressesByAddressIds = $getCustomerAddressesByAddressIds;
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
            $billingAddress = $this->getAddressByType($customer, 'default_billing');
            if ($billingAddress) {
                $company = trim((string)$billingAddress->getCompany());
            }

            if (empty($company)) {
                $shippingAddress = $this->getAddressByType($customer, 'default_shipping');
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

    /**
     * @param        $customer
     * @param string $type
     *
     * @return mixed|null
     * @throws LocalizedException
     */
    private function getAddressByType($customer, string $type)
    {
        $address = null;
        $addressId = $customer->getData($type);
        if ($addressId) {
            $address = $this->getCustomerAddressesByAddressIds->execute([$addressId])[$addressId] ?? null;
        }

        return $address;
    }
}
