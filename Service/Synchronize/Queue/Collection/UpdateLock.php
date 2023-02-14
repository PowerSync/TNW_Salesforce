<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Queue\Collection;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Throwable;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Model\Logger\Processor\UidProcessor;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Model\ResourceModel\FilterBlockedQueueRecords;
use TNW\Salesforce\Model\ResourceModel\Queue\Collection;
use Zend_Db_Expr;

class UpdateLock
{
    /** @var FilterBlockedQueueRecords */
    protected $filterBlockedQueueRecords;

    /** @var UidProcessor  */
    protected $uidProcessor;

    /** @var DateTime */
    protected $dateTime;

    public function __construct(
        FilterBlockedQueueRecords $filterBlockedQueueRecords,
        UidProcessor $uidProcessor,
        DateTime                            $dateTime
    ) {
        $this->filterBlockedQueueRecords = $filterBlockedQueueRecords;
        $this->uidProcessor = $uidProcessor;
        $this->dateTime = $dateTime;
    }

    /**
     * @param array $phase
     * @return array
     */
    public function getLockData(array $phase): array
    {
        $this->uidProcessor->refresh();

        $startStatus = $phase['startStatus'] ?? '';

        $lockData = [
            'status' => $phase['processStatus'],
            'transaction_uid' => $this->uidProcessor->uid(),
            'identify' => new Zend_Db_Expr('queue_id')
        ];

        if ($startStatus === Queue::STATUS_NEW || $startStatus === Queue::STATUS_ERROR) {
            $lockData['sync_at'] = $this->dateTime->gmtDate('c');
        }

        return $lockData;
    }

    /**
     * @param array $queueIds
     * @param array $phase
     * @param \TNW\Salesforce\Model\ResourceModel\Queue $resource
     * @return array
     * @throws LocalizedException
     * @throws Throwable
     */
    public function updateLock(
        array      $queueIds,
        array $phase,
        \TNW\Salesforce\Model\ResourceModel\Queue $resource
    ) {
        try {
            $conection = $resource->getConnection();
            if ($queueIds) {
                $lockData = $this->getLockData($phase);

                foreach (array_chunk($queueIds, ChunkSizeInterface::CHUNK_SIZE_200) as $ids) {
                    $conection->update(
                        $resource->getMainTable(),
                        $lockData,
                        $conection->prepareSqlCondition('queue_id', ['in' => $ids])
                    );
                }
            }
        } catch (Throwable $e) {
            $conection->rollBack();
            throw $e;
        }

        return $queueIds;
    }

    /**
     * @param Collection $idsCollection
     * @param int $page
     * @param int $limit
     * @return array
     * @throws LocalizedException
     */
    public function getIdsBatch(Collection $idsCollection, int $page = 1, int $limit = ChunkSizeInterface::CHUNK_SIZE_200): array
    {
        $this->resetCollection($idsCollection);
        if (!$idsCollection->getPageSize()) {
            $idsCollection->setPageSize($limit);
        }

        $idsCollection->setCurPage($page);

        if ($page > $idsCollection->getLastPageNumber()) {
            return [];
        }

        $ids = $idsCollection->getColumnValues($idsCollection->getResource()->getIdFieldName());
        $ids = $this->filterBlockedQueueRecords->execute($ids);

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
        $idsSelect->reset(Select::COLUMNS);
        $idsSelect->columns($idsCollection->getIdFieldName());

        return $idsCollection;
    }
}
