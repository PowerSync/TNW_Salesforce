<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load;

use Magento\Customer\Model\ResourceModel\Customer;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Service\Synchronize\Unit\Load\GetMappedAttributeCodesByMagentoType;

class GetCustomersByCustomerIds implements CleanableInstanceInterface
{
    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /** @var Customer */
    private $resource;

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var GetMappedAttributeCodesByMagentoType */
    private $getMappedAttributeCodesByMagentoType;

    /**
     * @param Customer                             $resource
     * @param CollectionFactory                    $collectionFactory
     * @param GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
     */
    public function __construct(
        Customer                             $resource,
        CollectionFactory                    $collectionFactory,
        GetMappedAttributeCodesByMagentoType $getMappedAttributeCodesByMagentoType
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
        $this->getMappedAttributeCodesByMagentoType = $getMappedAttributeCodesByMagentoType;
    }

    /**
     * @param array $entityIds
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_map('intval', $entityIds);
        $entityIds = array_unique($entityIds);

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processed[$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->cache[$entityId] = null;
                $this->processed[$entityId] = 1;
            }
        }

        if ($missedEntityIds) {
            foreach (array_chunk($missedEntityIds, ChunkSizeInterface::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $collection = $this->collectionFactory->create();
                $collection->addFieldToFilter(
                    $this->resource->getIdFieldName(),
                    ['in' => $entityIds]
                );
                foreach ($missedEntityIdsChunk as $missedEntityId) {
                    $this->cache[$missedEntityId] = $collection->getItemById($missedEntityId);
                }
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $this->cache[$entityId] ?? null;
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
