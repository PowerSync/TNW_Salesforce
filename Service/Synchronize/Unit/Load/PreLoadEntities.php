<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load;

use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoaderInterface;

class PreLoadEntities implements CleanableInstanceInterface
{
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var array */
    private $cache = [];

    /** @var array  */
    private $processed = [];

    /**
     * @param PreLoaderInterface $preLoader
     * @param array              $entityIds
     *
     * @return array
     */
    public function execute(PreLoaderInterface $preLoader, array $entityIds)
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_map('intval', $entityIds);
        $entityIds = array_unique($entityIds);
        $type = get_class($preLoader);

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processed[$type][$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->processed[$type][$entityId] = 1;
            }
        }

        if ($missedEntityIds) {
            $collection = $preLoader->getCollection();
            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $batchCollection = clone $collection;

                $batchCollection->addFieldToFilter(
                    $collection->getIdFieldName(),
                    ['in' => $missedEntityIdsChunk]
                );
                foreach ($batchCollection as $item) {
                    $this->cache[$type][$item->getId()] = $item;
                }
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            $item = $this->cache[$type][$entityId] ?? null;
            $item && $result[$entityId] = $item;
        }

        return $result;
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
