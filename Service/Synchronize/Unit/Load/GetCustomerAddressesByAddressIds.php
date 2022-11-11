<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load;

use Magento\Customer\Model\ResourceModel\Address;
use Magento\Customer\Model\Address as AddressModel;
use Magento\Customer\Model\ResourceModel\Address\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;

class GetCustomerAddressesByAddressIds implements CleanableInstanceInterface
{
    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var Address */
    private $resource;

    /**
     * @param Address           $resource
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        Address           $resource,
        CollectionFactory $collectionFactory
    ) {
        $this->resource = $resource;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param array $entityIds
     *
     * @return AddressModel[]
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
                $collection->addAttributeToSelect('*');
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
            $result[$entityId] = $this->cache[$entityId] ?? [];
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
