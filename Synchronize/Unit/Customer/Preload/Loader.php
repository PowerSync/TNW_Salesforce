<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Preload;

use Magento\Customer\Model\CustomerFactory;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\LoaderInterface;
use TNW\SForceEnterprise\Service\Synchronize\Unit\Load\GetCustomersByCustomerIds;

class Loader implements LoaderInterface
{
    /** @var CustomerFactory */
    private $factory;

    /** @var GetCustomersByCustomerIds */
    private $getCustomersByCustomerIds;

    /**
     * @param GetCustomersByCustomerIds $getCustomersByCustomerIds
     * @param CustomerFactory           $factory
     */
    public function __construct(
        GetCustomersByCustomerIds $getCustomersByCustomerIds,
        CustomerFactory           $factory
    ) {
        $this->factory = $factory;
        $this->getCustomersByCustomerIds = $getCustomersByCustomerIds;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entities): array
    {
        $entityIds = [];
        foreach ($entities as $entity) {
            $customerId = $entity->getCustomerId();
            $customerId && $entityIds[] = $customerId;
        }

        $customers = $this->getCustomersByCustomerIds->execute($entityIds);

        $result = [];
        foreach ($entities as $key => $requestEntity) {
            $entity = $customers[$requestEntity->getCustomerId()] ?? $this->factory->create();
            $entity && $result[$key] = $entity;
        }

        return $result;
    }
}
