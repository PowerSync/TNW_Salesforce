<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load\PreloadEntities;

use TNW\Salesforce\Service\Synchronize\Unit\Load\GetCustomerAddressesByAddressIds;
use TNW\Salesforce\Service\Synchronize\Unit\Load\PreloadEntities\AfterLoadExecutorInterface;

class PreloadAddresses implements AfterLoadExecutorInterface
{
    /** @var GetCustomerAddressesByAddressIds */
    private $getCustomerAddressesByAddressIds;

    public function __construct(
        GetCustomerAddressesByAddressIds $getCustomerAddressesByAddressIds
    ) {
        $this->getCustomerAddressesByAddressIds = $getCustomerAddressesByAddressIds;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entities, array $entityAdditionalByEntityId = []): array
    {
        $addressIds = [];
        $attributeCodes = [
            'default_billing',
            'default_shipping'
        ];
        foreach ($attributeCodes as $attributeCode) {
            foreach ($entities as $entity) {
                $addressId = $entity->getData($attributeCode);
                $addressId && $addressIds[] = $addressId;
            }
        }

        $addressIds && $this->getCustomerAddressesByAddressIds->execute($addressIds);

        return $entities;
    }
}
