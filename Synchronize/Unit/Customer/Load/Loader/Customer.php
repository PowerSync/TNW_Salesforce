<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer\Load\Loader;

use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Salesforce\Service\Synchronize\Unit\Load\PreLoadEntities;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoaderInterface;
use TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface;

/**
 * Load By Customer
 */
class Customer implements LoadLoaderInterface, PreLoaderInterface
{
    const LOAD_BY = 'customer';

    /**
     * @var CustomerFactory
     */
    private $customerFactory;

    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer
     */
    private $resourceCustomer;

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var PreLoadEntities */
    private $preLoadEntities;

    /**
     * ByCustomer constructor.
     * @param CustomerFactory $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     */
    public function __construct(
        CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer,
        CollectionFactory $collectionFactory,
        PreLoadEntities $preLoadEntities
    ) {
        $this->customerFactory = $customerFactory;
        $this->resourceCustomer = $resourceCustomer;
        $this->collectionFactory = $collectionFactory;
        $this->preLoadEntities = $preLoadEntities;
    }

    /**
     * Load Type
     *
     * @return string
     */
    public function loadBy()
    {
        return self::LOAD_BY;
    }

    /**
     * Load
     *
     * @param int $entityId
     * @param array $additional
     * @return \Magento\Customer\Model\Customer
     */
    public function load($entityId, array $additional)
    {
        $entity = $this->preLoadEntities->execute($this, [$entityId])[$entityId] ?? null;
        if ($entity) {
            return $entity;
        }

        $customer = $this->customerFactory->create();
        $this->resourceCustomer->load($customer, $entityId);

        return $customer;
    }

    /**
     * @inheritDoc
     */
    public function getCollection(): \Magento\Framework\Data\Collection\AbstractDb
    {
        return $this->collectionFactory->create();
    }
}
