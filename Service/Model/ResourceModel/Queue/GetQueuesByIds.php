<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\ResourceModel\Queue;

use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory;

class GetQueuesByIds implements CleanableInstanceInterface
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var array  */
    private $cache = [];

    /** @var array  */
    private $processed = [];

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param array $entityIds
     *
     * @return Queue[]
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
            foreach (array_chunk($missedEntityIds, ChunkSizeInterface::CHUNK_SIZE_200) as $missedEntityIdsChunk) {
                $collection = $this->collectionFactory->create();
                $collection->addFieldToFilter(
                    $collection->getIdFieldName(),
                    [
                        'in' => $missedEntityIdsChunk
                    ]
                );

                foreach ($collection as $item) {
                    $entityId = $item->getId();
                    $this->cache[$entityId] = $item;
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
     * @param Queue $queue
     *
     * @return void
     */
    public function fillCache(Queue $queue)
    {
        $queueId = $queue->getId();
        if($queueId) {
            $this->cache[$queueId] = $queue;
            $this->processed[$queueId] = 1;
        }
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->processed = [];
        $this->cache = [];
    }
}
