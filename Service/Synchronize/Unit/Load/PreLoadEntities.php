<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Load;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoader\AfterPreLoadExecutorsInterface;
use TNW\Salesforce\Synchronize\Unit\Load\PreLoaderInterface;
use TNW\Salesforce\Synchronize\Unit\Loader\EntityAbstract;
use TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface;

class PreLoadEntities implements CleanableInstanceInterface
{
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /**
     * @param  LoadLoaderInterface $preLoader
     * @param array              $entityIds
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(LoadLoaderInterface $preLoader, array $entityIds)
    {
        if (!$entityIds || !($preLoader instanceof PreLoaderInterface)) {
            return [];
        }

        $entityIds = array_map('intval', $entityIds);
        $entityIds = array_unique($entityIds);
        $type = $preLoader->loadBy();

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processed[$type][$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->processed[$type][$entityId] = 1;
            }
        }

        if ($missedEntityIds) {
            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $collection = $preLoader->createCollectionInstance();

                $collection->addFieldToFilter(
                    $collection->getIdFieldName(),
                    ['in' => $missedEntityIdsChunk]
                );
                $missedItems = [];
                foreach ($collection as $item) {
                    $itemId = $item->getId();
                    $this->cache[$type][$itemId] = $item;
                    $missedItems[$itemId] = $item;
                }
                if ($preLoader instanceof EntityAbstract) {
                    $preLoader->preloadSalesforceIds($collection->getItems());
                }
                if ($preLoader instanceof AfterPreLoadExecutorsInterface) {
                    foreach ($preLoader->getAfterPreLoadExecutors() as $afterLoadExecutor) {
                        $afterLoadExecutor->execute($missedItems);
                    }
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
