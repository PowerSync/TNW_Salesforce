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
    public const TYPE_ENTITY_ID_FIELD = 1;

    public const TYPE_METHOD = 2;

    public const TYPE_KEY = 3;

    /** @var array */
    private $processed = [];

    /** @var array */
    private $cache = [];

    /**
     * @param array           $entityIds
     * @param CreateInterface $loader
     * @param string          $entityIdFieldOrMethod
     * @param int             $getEntityIdType
     *
     * @return array
     */
    public function execute(
        array $entityIds,
        CreateInterface $loader,
        string $entityIdFieldOrMethod,
        int $getEntityIdType = self::TYPE_ENTITY_ID_FIELD
    ): array
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_unique($entityIds);

        $type = spl_object_hash($loader);

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
                foreach ($items as $key => $item) {
                    switch ($getEntityIdType) {
                        case self::TYPE_ENTITY_ID_FIELD:
                        default:
                            $entityId = $item[$entityIdFieldOrMethod] ?? null;
                            break;
                        case self::TYPE_METHOD:
                            $entityId = $item->$entityIdFieldOrMethod();
                            break;
                        case self::TYPE_KEY:
                            $entityId = $key;
                            break;
                    }
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
