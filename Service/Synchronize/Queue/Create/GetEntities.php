<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Queue\Create;

use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Synchronize\Queue\CreateInterface;

class GetEntities implements CleanableInstanceInterface
{
    /** @var array */
    private $processed = [];

    /** @var array */
    private $cache = [];

    public function execute(
        array $entityIds,
        CreateInterface $loader,
        string $entityIdField
    ): array
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_unique($entityIds);

        $type = spl_object_id($loader);

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processed[$type][$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->cache[$type][$entityId] = [];
                $this->processed[$type][$entityId] = 1;
            }
        }

        if ($missedEntityIds) {
            foreach (array_chunk($missedEntityIds, ChunkSizeInterface::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $items = $loader->entities($missedEntityIdsChunk);
                foreach ($items as $item) {
                    $entityId = $item[$entityIdField] ?? null;
                    $entityId !== null && $this->cache[$type][$entityId][] = $item;
                }
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            $items = $this->cache[$type][$entityId] ?? [];
            foreach ($items as $item) {
                $result[] = $item;
            }
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
