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
     * @param int $count
     * @return array
     * @throws LocalizedException
     */
    public function getIdsBatch(Collection $idsCollection, int $count): array
    {
        if ($count <= 0) {
            return [];
        }

        $this->resetCollection($idsCollection);
        if (!$idsCollection->getPageSize()) {
            $idsCollection->setPageSize($count);
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
        $idsSelect->reset(Select::COLUMNS);
        $idsSelect->columns($idsCollection->getIdFieldName());
        $idsSelect->columns($idsCollection->getIdFieldName());

        $idsSelect->distinct(true);
        $idsSelect
            ->joinInner(
                ['relation' => $idsCollection->getTable('tnw_salesforce_entity_queue_relation')],
                'main_table.queue_id = relation.queue_id',
                []
            );

        $columnExpression = sprintf(
            "IFNULL(SUM(relation.parent_status IN ('%s') OR relation.parent_id IS NULL), 0)",
            implode("', '", Queue::SUCCESS_STATUSES)
        );

        $idsSelect->columns(
            ['synced_parents' => new \Zend_Db_Expr($columnExpression)]
        );

        $idsSelect->columns(
            ['total_parents' => new \Zend_Db_Expr('COUNT(relation.parent_status)')]
        );

        $idsSelect->having('total_parents = synced_parents');

        $idsSelect->group('main_table.queue_id');
        return $idsCollection;
    }
}
