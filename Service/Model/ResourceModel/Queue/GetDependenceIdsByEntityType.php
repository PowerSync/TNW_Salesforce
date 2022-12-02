<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\ResourceModel\Queue;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\ResourceModel\Queue;

/**
 *  Class GetDependenceIdsByEntityType
 */
class GetDependenceIdsByEntityType implements CleanableInstanceInterface
{
    /** @var Queue */
    private $queueResource;

    /** @var array  */
    private $cache = [];

    /** @var array  */
    private $processed = [];

    /**
     * @param Queue $queueResource
     */
    public function __construct(
        Queue $queueResource
    ) {
        $this->queueResource = $queueResource;
    }

    public function execute(array $entityIds, string $entityType): array
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_map('strval', $entityIds);
        $entityIds = array_unique($entityIds);

        $missedEntityIds = [];
        foreach ($entityIds as $relationQueueId) {
            if (!isset($this->processed[$entityType][$relationQueueId])) {
                $missedEntityIds[] = $relationQueueId;
                $this->cache[$entityType][$relationQueueId] = [];
                $this->processed[$entityType][$relationQueueId] = 1;
            }
        }

        if ($missedEntityIds) {
            $select = $this->getSelect();
            $connection = $this->queueResource->getConnection();
            foreach (array_chunk($missedEntityIds, ChunkSizeInterface::CHUNK_SIZE_200) as $missedEntityIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('relation.queue_id IN(?)', $missedEntityIdsChunk);
                $batchSelect->where('queue.entity_type = ?', $entityType);

                $items = $connection->fetchAll($batchSelect);
                foreach ($items as $item) {
                    $relationQueueId = $item['relation_queue_id'];
                    $value = $item['queue_id'];
                    $this->cache[$entityType][$relationQueueId][$value] = $value;
                }
            }
        }

        $result = [];
        foreach ($entityIds as $relationQueueId) {
            $result[$relationQueueId] = $this->cache[$entityType][$relationQueueId] ?? [];
        }

        return $result;
    }

    /**
     * @return Select
     * @throws LocalizedException
     */
    private function getSelect(): Select
    {
        $resource = $this->queueResource;
        $select = $resource->getConnection()->select()
            ->from(['relation' => $resource->getTable('tnw_salesforce_entity_queue_relation')], [])
            ->joinInner(
                ['queue' => $resource->getMainTable()],
                'relation.parent_id = queue.queue_id',
                ['queue_id']
            );

        $select->reset(Select::COLUMNS);
        $select->columns(
            [
                'queue_id' => 'queue.queue_id',
                'relation_queue_id' => 'relation.queue_id'
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
