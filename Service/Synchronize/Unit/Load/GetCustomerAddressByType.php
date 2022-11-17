<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load;

use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Address;
use Magento\Framework\Exception\LocalizedException;
use TNW\SForceEnterprise\SForceBusiness\Synchronize\Entity\Customer\Generate;

class GetCustomerAddressByType
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
     * @param Customer $customer
     *
     * @return Address|null
     * @throws LocalizedException
     */
    public function getDefaultBillingAddress(Customer $customer): ?Address
    {
        return (bool)$customer->getData('generated') ? $customer->getData(Generate::DEFAULT_GENERATED_BILLING_ADDRESS) : $this->execute($customer, 'default_billing');
    }

    /**
     * @param Customer $customer
     *
     * @return Address|null
     * @throws LocalizedException
     */
    public function getDefaultShippingAddress(Customer $customer): ?Address
    {
        return (bool)$customer->getData('generated') ? $customer->getData(Generate::DEFAULT_GENERATED_SHIPPING_ADDRESS) : $this->execute($customer, 'default_shipping');
    }

    /**
     * @param Customer $customer
     * @param string   $type
     *
     * @return Address|null
     * @throws LocalizedException
     */
    private function execute(Customer $customer, string $type): ?Address
    {
        $address = null;
        $addressId = $customer->getData($type);
        if ($addressId) {
            $address = $this->getCustomerAddressesByAddressIds->execute([$addressId])[$addressId] ?? null;
        }

        return $address;
    }
}
