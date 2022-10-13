<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Preload;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetMappedAttributeCodesByMagentoType;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\LoaderInterface;

class Loader implements LoaderInterface
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var GetMappedAttributeCodesByMagentoType */
    private $getMappedAttributeCodesByMagentoType;

    /** @var array */
    private $processed = [];

    /** @var array */
    private $cache = [];

    /**
     * @param CollectionFactory                    $collectionFactory
     * @param GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
     */
    public function __construct(
        CollectionFactory                    $collectionFactory,
        GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->getMappedAttributeCodesByMagentoType = $getMappedAttributeCodesByMagentoType;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entities): array
    {
        $collection = $this->collectionFactory->create();

        $entityIds = [];
        foreach ($entities as $entity) {
            $entityIds[] = $entity->getCustomerId();
        }

        $entityIds = array_unique($entityIds);
        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processed[$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->processed[$entityId] = 1;
            }
        }

        if ($missedEntityIds) {
            $magentoType = Mapper::MAGENTO_ENTITY_TYPE_CUSTOMER;
            $attributeCodes = $this->getMappedAttributeCodesByMagentoType->execute([$magentoType])[$magentoType] ?? [];
            $attributeCodes && $collection->addAttributeToSelect($attributeCodes, 'left');
            $collection->addFieldToFilter(
                $collection->getIdFieldName(),
                ['in' => $entityIds]
            );
            foreach ($collection as $item) {
                $this->cache[$item->getId()] = $item;
            }
        }

        $result = [];
        foreach ($entities as $key => $requestEntity) {
            $entity = $this->cache[$requestEntity->getCustomerId()] ?? null;
            $entity && $result[$key] = $entity;
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->cache = [];
        $this->processed = [];
    }
}
