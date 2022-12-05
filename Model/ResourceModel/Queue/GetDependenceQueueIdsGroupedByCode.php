<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Model\ResourceModel\Queue;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\ResourceModel\Queue;

class GetDependenceQueueIdsGroupedByCode implements CleanableInstanceInterface
{
    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /** @var Queue */
    private $resource;

    /**
     * @param Queue $resource
     */
    public function __construct(
        Queue $resource
    ) {
        $this->resource = $resource;
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

        $entityIds = array_map('strval', $entityIds);
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
            $select = $this->getSelect();
            $connection = $this->resource->getConnection();
            foreach (array_chunk($missedEntityIds, ChunkSizeInterface::CHUNK_SIZE_200) as $missedEntityIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('relation.queue_id IN(?)', $missedEntityIdsChunk);

                $items = $connection->fetchAll($batchSelect);
                foreach ($items as $item) {
                    $entityId = $item['queue_id'];
                    $code = $item['code'];
                    $dependenceQueueId = $item['dependence_queue_id'] ?? '';
                    $dependenceQueueId && $this->cache[$entityId][$code][] = $dependenceQueueId;
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
     * @return Select
     * @throws LocalizedException
     */
    private function getSelect(): Select
    {
        $resource = $this->resource;

        $select = $resource->getConnection()->select();

        $select->from(
            [
                'relation' => $resource->getTable('tnw_salesforce_entity_queue_relation')
            ],
            []
        );
        $select->joinInner(
            ['queue' => $resource->getMainTable()],
            'relation.parent_id = queue.queue_id',
            ['queue_id']
        );

        $select->reset(Select::COLUMNS);
        $select->columns(
            [
                'queue_id' => 'relation.queue_id',
                'code' => 'queue.code',
                'dependence_queue_id' => 'queue.queue_id'
            ]
        );

        return $select;
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
