<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities;

use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Synchronize\Unit\EntityLoaderAbstract;
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
            $entityId = spl_object_hash($requestEntity);
            $entities[$entityId] = $requestEntity;
        }

        $cacheKey = spl_object_hash($preLoader);

        $missedEntities = [];
        foreach ($entities as $entityId => $entity) {
            if (!isset($this->processed[$cacheKey][$entityId])) {
                $missedEntities[$entityId] = $entity;
                $this->processed[$cacheKey][$entityId] = 1;
            }
        }

        if ($missedEntities) {
            foreach (array_chunk($missedEntities, ChunkSizeInterface::CHUNK_SIZE_200, true) as $missedEntitiesChunk) {
                $loadedEntities = $missedEntitiesChunk;
                $loaders = $preLoader->getLoaders();
                ksort($loaders);
                foreach ($loaders as $beforePreloadExecutor) {
                    $loadedEntities = $beforePreloadExecutor->execute($loadedEntities);
                }

                $entitiesToCache = [];
                foreach ($missedEntitiesChunk as $missedEntityId => $missedEntity) {
                    if (!array_key_exists($missedEntityId, $loadedEntities)) {
                        throw new \RuntimeException('Undefined Entity!');
                    }
                    $entitiesToCache[$missedEntityId] = $loadedEntities[$missedEntityId] ;
                }

                $afterPreloadExecutors = $preLoader->getAfterPreloadExecutors();
                ksort($afterPreloadExecutors);
                foreach ($afterPreloadExecutors as $afterPreloadExecutor) {
                    $entitiesToCache = $afterPreloadExecutor->execute($entitiesToCache, $missedEntitiesChunk);
                }

                if ($preLoader instanceof EntityLoaderAbstract) {
                    $preLoader->preloadSalesforceIds($entitiesToCache);
                }

                foreach ($entitiesToCache as $entityId => $entity) {
                    if ($entity) {
                        $data = $entity->getData('preloadInfo') ?? [];
                        $data['preLoader']['object'] = $preLoader;
                        $entity->setData('preloadInfo', $data);
                    }
                    $this->cache[$cacheKey][$entityId] = $entity;
                }
            }
        }

        $result = [];
        foreach ($entities as $entityId => $entity) {
            $result[$entityId] = $this->cache[$cacheKey][$entityId] ?? null;
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
