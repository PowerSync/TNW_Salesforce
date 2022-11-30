<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load;

use Magento\Eav\Model\Entity\Type;
use Magento\Eav\Model\ResourceModel\Entity\Type\CollectionFactory;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;

class GetEntityTypeByCode implements CleanableInstanceInterface
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var array */
    private $cache = [];

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    public function execute(array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_map('strval', $entityIds);
        $entityIds = array_unique($entityIds);

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->cache[$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->cache[$entityId] = null;
            }
        }

        if ($missedEntityIds) {
            foreach (array_chunk($missedEntityIds, ChunkSizeInterface::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $collection = $this->collectionFactory->create();
                $collection->addFieldToFilter(
                    'entity_type_code',
                    ['in' => $missedEntityIdsChunk]
                );

                /** @var Type $item */
                foreach ($collection as $item) {
                    $code = $item->getEntityTypeCode();
                    $this->cache[$code] = $item;
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
    }
}
