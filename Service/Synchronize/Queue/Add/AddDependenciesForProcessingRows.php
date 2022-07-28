<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Queue\Add;

use Magento\Framework\App\ResourceConnection;
use TNW\Salesforce\Model\Queue;

/**
 *  Resolving duplicate object creation on parallel processes.
 */
class AddDependenciesForProcessingRows
{
    private const CHUNK_SIZE = 100;

    /** @var ResourceConnection */
    private $resourceConnection;


    /**
     * @param ResourceConnection $resourceConnection
     */
    public function __construct(
        ResourceConnection $resourceConnection
    ) {
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * Create additional dependencies for queues in status Processing.
     *
     * @param array $queueDataToSave
     * @param array $dependencies
     *
     * @return array
     */
    public function execute(array $queueDataToSave, array $dependencies): array
    {
        if (!$queueDataToSave || !$dependencies) {
            return $dependencies;
        }
        $listOfCandidates = [];
        foreach ($queueDataToSave as $item) {
            $queueId = $item['queue_id'] ?? null;
            $uniqueHash = $item[Queue::UNIQUE_HASH] ?? null;
            if ($queueId && $uniqueHash) {
                $listOfCandidates[$uniqueHash] = $queueId;
            }
        }
        $parentItems = $this->getProcessingRowsDataByUniqueHashIds(array_keys($listOfCandidates));
        foreach ($parentItems as $parentUniqueHash => $parentQueueId) {
            $childQueueId = $listOfCandidates[$parentUniqueHash] ?? null;
            if ($parentQueueId && $parentUniqueHash && $childQueueId) {
                $dependencies[] = [
                    'parent_id' => $parentQueueId,
                    'queue_id' => $childQueueId
                ];
            }
        }

        return $dependencies;
    }

    /**
     * Get rows in Processing statuses
     *
     * @param array $uniqueHashIds
     *
     * @return array
     */
    private function getProcessingRowsDataByUniqueHashIds(array $uniqueHashIds): array
    {
        if (!$uniqueHashIds) {
            return [];
        }
        $result = [];
        $connection = $this->resourceConnection->getConnection();
        foreach (array_chunk($uniqueHashIds, self::CHUNK_SIZE) as $uniqueHashIdsChunk) {
            $select = $connection->select()->from('tnw_salesforce_entity_queue', ['queue_id', Queue::UNIQUE_HASH]);
            $select->where('status IN(?)', [
                Queue::STATUS_PROCESS_INPUT_LOOKUP,
                Queue::STATUS_PROCESS_INPUT_UPSERT,
                Queue::STATUS_PROCESS_OUTPUT_LOOKUP,
                Queue::STATUS_PROCESS_OUTPUT_UPSERT,
            ]);
            $select->where(sprintf('%s IN(?)', Queue::UNIQUE_HASH), array_keys($uniqueHashIdsChunk));
            $items = $connection->fetchAll($select);
            if ($items) {
                foreach ($items as $item) {
                    $queueId = $item['queue_id'] ?? null;
                    $uniqueHash = $item[Queue::UNIQUE_HASH] ?? null;
                    if ($queueId && $uniqueHash) {
                        $result[$uniqueHash] = $queueId;
                    }
                }
            }
        }

        return $result;
    }
}
