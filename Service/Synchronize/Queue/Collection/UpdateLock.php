<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Queue\Collection;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Throwable;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\ResourceModel\FilterBlockedQueueRecords;
use TNW\Salesforce\Model\ResourceModel\Queue\Collection;

class UpdateLock
{
    /** @var FilterBlockedQueueRecords */
    protected $filterBlockedQueueRecords;

    /**
     * @param FilterBlockedQueueRecords $filterBlockedQueueRecords
     */
    public function __construct(FilterBlockedQueueRecords $filterBlockedQueueRecords)
    {
        $this->filterBlockedQueueRecords = $filterBlockedQueueRecords;
    }

    /**
     * @param array $ids
     * @param Collection $idsCollection
     * @param array $lockData
     * @return int|null
     * @throws Throwable
     */
    public function updateLock(
        array      $ids,
        \TNW\Salesforce\Model\ResourceModel\Queue $resource,
        array      $lockData
    ) {
        $queueIds = [];
        try {
            $conection = $resource->getConnection();
            if ($ids) {

                $conection->beginTransaction();
                $queueIds = $this->filterBlockedQueueRecords->execute($ids);

                $conection->update(
                    $resource->getMainTable(),
                    $lockData,
                    $conection->prepareSqlCondition('queue_id', ['in' => $queueIds])
                );
                $conection->commit();
            }
        } catch (Throwable $e) {
            $conection->rollBack();
            throw $e;
        }

        return count($queueIds);
    }

    /**
     * @param Collection $collection
     * @param array $lockData
     * @param int $maxCount
     * @return int|null
     * @throws LocalizedException
     * @throws Throwable
     */
    public function execute(
        Collection $collection,
        array      $lockData,
        int        $maxCount = Config::SFORCE_BASE_UPDATE_LIMIT
    ): int {
        $idsCollection = clone $collection;
        $idsCollection->getSelect()->group('identify');
        $idsCollection = $this->resetCollection($idsCollection);
        $counter = 0;

        while (($ids = $this->getIdsBatch($idsCollection, $maxCount)) && $counter < $maxCount) {
            $counter += $this->updateLock($ids, $idsCollection->getResource(), $lockData);
        }

        return $counter;
    }

    /**
     * @param Collection $idsCollection
     * @param $limit
     * @return array
     * @throws LocalizedException
     */
    public function getIdsBatch(Collection $idsCollection, int $limit = Collection::UPDATE_CHUNK)
    {
        $idsCollection->clear();
        if (!$idsCollection->getPageSize()) {
            $limit = min($limit, Collection::UPDATE_CHUNK);
            $page = 1;
            $idsCollection->setPageSize($limit);
        } else {
            $page = $idsCollection->getCurPage() + 1;
        }

        $idsCollection->setCurPage($page);

        if ($page > $idsCollection->getLastPageNumber()) {
            return [];
        }

        $ids = $idsCollection->getColumnValues($idsCollection->getResource()->getIdFieldName());

        return $ids;
    }

    /**
     * @param Collection $idsCollection
     * @return Collection
     */
    public function resetCollection(Collection $idsCollection)
    {
        $idsCollection->clear();
        $idsSelect = $idsCollection->getSelect();
        $idsSelect->reset(Select::ORDER);
        $idsSelect->reset(Select::LIMIT_COUNT);
        $idsSelect->reset(Select::LIMIT_OFFSET);
        $idsSelect->reset(Select::COLUMNS);
        $idsSelect->columns($idsCollection->getIdFieldName());

        return $idsCollection;
    }

}
