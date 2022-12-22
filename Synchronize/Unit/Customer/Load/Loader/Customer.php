<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer\Load\Loader;

use Magento\Customer\Model\CustomerFactory;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\Model\AbstractModel;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetMappedAttributeCodesByMagentoType;
use TNW\Salesforce\Service\Synchronize\Unit\Load\PreLoadEntities;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoaderInterface;
use TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface;

/**
 * Load By Customer
 */
class Customer implements LoadLoaderInterface, PreLoaderInterface
{
    const LOAD_BY = 'customer';

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var PreLoadEntities */
    private $preLoadEntities;

    /** @var GetMappedAttributeCodesByMagentoType */
    private $getMappedAttributeCodesByMagentoType;

    /** @var array */
    private $afterLoadExecutors;

    /** @var CustomerFactory */
    private $factory;

    /** @var array */
    private $groupKeys;

    /**
     * ByCustomer constructor.
     *
     * @param CustomerFactory                      $factory
     * @param CollectionFactory                    $collectionFactory
     * @param PreLoadEntities                      $preLoadEntities
     * @param GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
     * @param array                                $afterLoadExecutors
     * @param array                                $groupKeys
     */
    public function __construct(
        CustomerFactory $factory,
        CollectionFactory $collectionFactory,
        PreLoadEntities $preLoadEntities,
        GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType,
        array $afterLoadExecutors = [],
        array $groupKeys = []
    ) {
        $this->factory = $factory;
        $this->collectionFactory = $collectionFactory;
        $this->preLoadEntities = $preLoadEntities;
        $this->getMappedAttributeCodesByMagentoType = $getMappedAttributeCodesByMagentoType;
        $this->afterLoadExecutors = $afterLoadExecutors;
        $this->groupKeys = $groupKeys;
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
        return $this->preLoadEntities->execute($this, [$entityId], [$entityId => $additional])[$entityId] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function createCollectionInstance(): ?AbstractDb
    {
        $collection = $this->collectionFactory->create();

        return $collection;
    }

    /**
     * @inheritDoc
     */
    public function getAfterPreLoadExecutors(): array
    {
        return $this->afterLoadExecutors;
    }

    /**
     * @inheritDoc
     */
    public function createEmptyEntity(): AbstractModel
    {
        return $this->factory->create();
    }

    /**
     * @inheritDoc
     */
    public function getGroupValue(array $loadAdditional): string
    {
        $groupValue = '';
        foreach ($this->groupKeys as $groupKey) {
            $value = $loadAdditional[$groupKey];
            if($value) {
                $groupValue .= $value;
            }
        }

        return $groupValue ?: self::DEFAULT_CACHE_KEY;
    }
}
