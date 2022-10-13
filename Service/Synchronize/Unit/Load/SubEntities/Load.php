<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities;

use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Synchronize\Unit\Load\EntityLoader\EntityPreLoaderInterface;

class Load implements CleanableInstanceInterface
{
    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /**
     * @param EntityPreLoaderInterface $preLoader
     * @param array                    $requestEntities
     *
     * @return array
     */
    public function execute(EntityPreLoaderInterface $preLoader, array $requestEntities): array
    {
        if (!$requestEntities) {
            return [];
        }

        $entities = [];
        foreach ($requestEntities as $requestEntity) {
            $entityId = spl_object_id($requestEntity);
            $entities[$entityId] = $requestEntity;
        }

        $cacheKey = get_class($preLoader);
        $entities = array_unique($entities);

        $missedEntities = [];
        foreach ($entities as $entityId => $entity) {
            if (!isset($this->processed[$entityId])) {
                $missedEntities[$entityId] = $entity;
                $this->processed[$cacheKey][$entityId] = 1;
            }
        }

        if ($missedEntities) {
            foreach (array_chunk($missedEntities, ChunkSizeInterface::CHUNK_SIZE_200, true) as $missedEntitiesChunk) {
                foreach ($preLoader->getBeforePreloadExecutors() as $beforePreloadExecutor) {
                    $missedEntitiesChunk = $beforePreloadExecutor->execute($missedEntitiesChunk);
                }

                $loadedEntities = $preLoader->getLoader()->execute($missedEntitiesChunk);

                $entitiesToCache = [];
                foreach ($missedEntitiesChunk as $missedEntityId => $missedEntity) {
                    $entitiesToCache[$missedEntityId] = $loadedEntities[$missedEntityId] ?? $preLoader->createEmptyEntity();
                }

                foreach ($preLoader->getAfterPreloadExecutors() as $afterPreloadExecutor) {
                    $entitiesToCache = $afterPreloadExecutor->execute($entitiesToCache, $missedEntitiesChunk);
                }

                foreach ($entitiesToCache as $entityId => $entity) {
                    $this->cache[$cacheKey][$entityId] = $entity;
                }
            }
        }

        $result = [];
        foreach ($entities as $entityId => $entity) {
            $result[$entityId] = $this->cache[$cacheKey][$entityId];
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