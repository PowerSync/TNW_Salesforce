<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer\Load\Loader;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DataObject;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetMappedAttributeCodesByMagentoType;
use TNW\Salesforce\Service\Synchronize\Unit\Load\PreLoadEntities;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoader\AfterPreLoadExecutorsInterface;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoaderInterface;
use TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface;

/**
 * Load By Customer
 */
class Customer implements LoadLoaderInterface, PreLoaderInterface, AfterPreLoadExecutorsInterface
{
    const LOAD_BY = 'customer';

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
     * @param CollectionFactory                              $collectionFactory
     * @param PreLoadEntities                                $preLoadEntities
     * @param GetMappedAttributeCodesByMagentoType           $getMappedAttributeCodesByMagentoType
     * @param array                                          $afterPreLoadLoadExecutors
     */
    public function __construct(
        CollectionFactory $collectionFactory,
        PreLoadEntities $preLoadEntities,
        GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType,
        array $afterPreLoadLoadExecutors = []
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->preLoadEntities = $preLoadEntities;
        $this->getMappedAttributeCodesByMagentoType = $getMappedAttributeCodesByMagentoType;
        $this->afterPreLoadLoadExecutors = $afterPreLoadLoadExecutors;
    }

    /**
     * @inheritDoc
     */
    public function loadBy()
    {
        return self::LOAD_BY;
    }

    /**
     * @inheritDoc
     */
    public function load($entityId, array $additional)
    {
        return $this->preLoadEntities->execute($this, [$entityId])[$entityId] ?? null;
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
