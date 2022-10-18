<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load\Loaders;

use Magento\Customer\Model\CustomerFactory;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\LoaderInterface;
use TNW\SForceEnterprise\Service\Synchronize\Unit\Load\GetCustomersByCustomerIds;

class CustomersByMethodLoader implements LoaderInterface
{
    public const GET_CUSTOMER_ID_METHOD = 'getCustomerId';
    public const GET_SUPER_USER_ID_METHOD = 'getSuperUserId';

    /** @var CustomerFactory */
    private $factory;

    /** @var GetCustomersByCustomerIds */
    private $getCustomersByCustomerIds;

    /** @var string */
    private $method;

    /**
     * @param GetCustomersByCustomerIds $getCustomersByCustomerIds
     * @param CustomerFactory           $factory
     */
    public function __construct(
        GetCustomersByCustomerIds $getCustomersByCustomerIds,
        CustomerFactory           $factory,
        string $method
    ) {
        $this->factory = $factory;
        $this->getCustomersByCustomerIds = $getCustomersByCustomerIds;
        $this->method = $method;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entities): array
    {
        $entityIds = [];
        $method = $this->method;
        foreach ($entities as $entity) {
            $customerId = $entity->$method();
            $customerId && $entityIds[] = $customerId;
        }

        $customers = $this->getCustomersByCustomerIds->execute($entityIds);

        $result = [];
        foreach ($entities as $key => $requestEntity) {
            $entity = $customers[$requestEntity->$method()] ?? $this->factory->create();
            $entity && $result[$key] = $entity;
        }

        return $result;
    }
}
