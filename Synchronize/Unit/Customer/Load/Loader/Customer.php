<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer\Load\Loader;

use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetMappedAttributeCodesByMagentoType;
use TNW\Salesforce\Service\Synchronize\Unit\Load\PreLoadEntities;
use TNW\Salesforce\Service\Synchronize\Unit\Load\PreloadEntities\AfterLoadExecutorInterface;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoader\AfterPreLoadExecutorsInterface;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoaderInterface;
use TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface;

/**
 * Load By Customer
 */
class Customer implements LoadLoaderInterface, PreLoaderInterface, AfterPreLoadExecutorsInterface
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

    /** @var GetMappedAttributeCodesByMagentoType */
    private $getMappedAttributeCodesByMagentoType;

    /** @var array */
    private $afterPreLoadLoadExecutors;

    /**
     * ByCustomer constructor.
     *
     * @param CustomerFactory                                $customerFactory
     * @param \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer
     * @param CollectionFactory                              $collectionFactory
     * @param PreLoadEntities                                $preLoadEntities
     * @param GetMappedAttributeCodesByMagentoType           $getMappedAttributeCodesByMagentoType
     * @param array                                          $afterPreLoadLoadExecutors
     */
    public function __construct(
        CustomerFactory $customerFactory,
        \Magento\Customer\Model\ResourceModel\Customer $resourceCustomer,
        CollectionFactory $collectionFactory,
        PreLoadEntities $preLoadEntities,
        GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType,
        array $afterPreLoadLoadExecutors = []
    ) {
        $this->customerFactory = $customerFactory;
        $this->resourceCustomer = $resourceCustomer;
        $this->collectionFactory = $collectionFactory;
        $this->preLoadEntities = $preLoadEntities;
        $this->getMappedAttributeCodesByMagentoType = $getMappedAttributeCodesByMagentoType;
        $this->afterPreLoadLoadExecutors = $afterPreLoadLoadExecutors;
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
    public function createCollectionInstance(): AbstractDb
    {
        $collection = $this->collectionFactory->create();

        $magentoType = Mapper::MAGENTO_ENTITY_TYPE_CUSTOMER;
        $attributeCodes = $this->getMappedAttributeCodesByMagentoType->execute([$magentoType])[$magentoType] ?? [];
        $attributeCodes && $collection->addAttributeToSelect($attributeCodes, 'left');

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getAfterPreLoadExecutors(): array
    {
        return $this->afterPreLoadLoadExecutors;
    }
}
