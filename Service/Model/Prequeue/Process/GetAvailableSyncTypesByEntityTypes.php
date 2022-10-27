<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Prequeue\Process;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\ResourceModel\PreQueue;

class GetAvailableSyncTypesByEntityTypes implements CleanableInstanceInterface
{
    /** @var array  */
    private $cache = [];

    /** @var array  */
    private $processed = [];

    /** @var PreQueue */
    private $resource;

    /**
     * @param PreQueue $resource
     */
    public function __construct(
        PreQueue $resource
    )
    {
        $this->resource = $resource;
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
            if (!isset($this->processed[$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->cache[$entityId] = [];
                $this->processed[$entityId] = 1;
            }
        }

        if ($missedEntityIds) {
            $select = $this->getSelect();
            $connection = $this->resource->getConnection();
            foreach (array_chunk($missedEntityIds, ChunkSizeInterface::CHUNK_SIZE_200) as $missedEntityIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('entity_type IN(?)', $missedEntityIdsChunk);

                $items = $connection->fetchAll($batchSelect);
                foreach ($items as $item) {
                    $entityId = $item['entity_type'];
                    $value = $item['sync_type'];
                    $this->cache[$entityId][$value] = $value;
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
     * @return Select
     * @throws LocalizedException
     */
    private function getSelect(): Select
    {
        $connection = $this->resource->getConnection();
        $columns = ['entity_type', 'sync_type'];
        $select = $connection->select()->from($this->resource->getMainTable(), $columns);
        $select->group($columns);

        return $select;
    }

    /**
     * @return void
     */
    public function clearLocalCache(): void
    {
        $this->cache = [];
        $this->processed = [];
    }
}
