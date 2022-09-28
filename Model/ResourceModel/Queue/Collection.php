<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Salesforce\Model\ResourceModel\Queue;

use Exception;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Data\Collection\Db\FetchStrategyInterface;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\DataObject;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Psr\Log\LoggerInterface;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Model\ResourceModel\FilterBlockedQueueRecords;
use TNW\Salesforce\Model\ResourceModel\Queue as ResourceQueue;

/**
 * Queue Collection
 *
 * @method ResourceQueue getResource()
 */
class Collection extends AbstractCollection
{
    private const UPDATE_CHUNK = 500;

    /**
     * @var string
     */
    protected $_idFieldName = 'queue_id';

    /**
     * @var FilterBlockedQueueRecords
     */
    private $filterBlockedQueueRecords;

    /** @var LoggerInterface */
    private $logger;

    /**
     * @param EntityFactoryInterface    $entityFactory
     * @param LoggerInterface           $logger
     * @param FetchStrategyInterface    $fetchStrategy
     * @param ManagerInterface          $eventManager
     * @param FilterBlockedQueueRecords $filterBlockedQueueRecords
     * @param AdapterInterface|null     $connection
     * @param AbstractDb|null           $resource
     */
    public function __construct(
        EntityFactoryInterface    $entityFactory,
        LoggerInterface           $logger,
        FetchStrategyInterface    $fetchStrategy,
        ManagerInterface          $eventManager,
        FilterBlockedQueueRecords $filterBlockedQueueRecords,
        AdapterInterface          $connection = null,
        AbstractDb                $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->filterBlockedQueueRecords = $filterBlockedQueueRecords;
        $this->logger = $logger;
    }

    /**
     * @inheritDoc
     */
    public function save()
    {
        $itemsToInsert = [];
        $resource = $this->getResource();
        $connection = $resource->getConnection();
        $exceptionMessages = [];
        try {
            foreach ($this as $item) {
                if ($item->getId()) {
                    $itemsToInsert[] = $resource->convertToArray($item);
                }
            }
        } catch (\Throwable $e) {
            $message = [
                $e->getMessage(),
                $e->getTraceAsString()
            ];
            $exceptionMessages[] = implode(PHP_EOL, $message);
        }

        foreach (array_chunk($itemsToInsert, ChunkSizeInterface::CHUNK_SIZE_200) as $itemsToInsertChunk) {
            try {
                $connection->beginTransaction();
                $connection->insertOnDuplicate($this->getMainTable(), $itemsToInsertChunk);
                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
                $message = [
                    $e->getMessage(),
                    $e->getTraceAsString()
                ];
                $exceptionMessages[] = implode(PHP_EOL, $message);
            }
        }

        foreach ($exceptionMessages as $exceptionMessage) {
            $this->logger->error($exceptionMessage);
        }

        $this->saveDependence($this);
    }

    /**
     * Save Dependence
     *
     * @param Collection $collection
     */
    public function saveDependence(Collection $collection): void
    {
        $itemsToInsert = [];
        /** @var Queue $object */
        foreach ($collection as $object) {
            $dependence = $object->getDependence();
            foreach ($dependence as $item) {
                $dependenceId = $item->getId();
                if (empty($dependenceId)) {
                    continue;
                }

                $itemsToInsert[] = [
                    'queue_id' => $object->getId(),
                    'parent_id' => $dependenceId
                ];
            }
        }

        if (empty($itemsToInsert)) {
            return;
        }

        $connection = $this->getConnection();
        $tableName = $this->getTable('tnw_salesforce_entity_queue_relation');
        $exceptionMessages = [];
        foreach (array_chunk($itemsToInsert, ChunkSizeInterface::CHUNK_SIZE_200) as $itemsToInsertChunk) {
            try {
                $connection->beginTransaction();
                $connection->insertOnDuplicate($tableName, $itemsToInsertChunk);
                $connection->commit();
            } catch (\Throwable $e) {
                $connection->rollBack();
                $message = [
                    $e->getMessage(),
                    $e->getTraceAsString()
                ];
                $exceptionMessages[] = implode(PHP_EOL, $message);
            }
        }

        foreach ($exceptionMessages as $exceptionMessage) {
            $this->logger->error($exceptionMessage);
        }
    }

    /**
     * @return \TNW\Salesforce\Model\Queue[]
     */
    public function getItemsWithoutLoad()
    {
        return $this->_items;
    }

    /**
     * Add Filter To Sync Type
     *
     * @param int $syncType
     *
     * @return Collection
     */
    public function addFilterToSyncType($syncType)
    {
        return $this->addFieldToFilter('main_table.sync_type', $syncType);
    }

    /**
     * Add Filter To Code
     *
     * @param string $code
     *
     * @return Collection
     */
    public function addFilterToCode($code)
    {
        return $this->addFieldToFilter('main_table.code', $code);
    }

    /**
     * Add Filter To Code
     *
     * @param string $code
     *
     * @return Collection
     */
    public function addFilterToStatus($code)
    {
        return $this->addFieldToFilter('main_table.status', $code);
    }

    /**
     * Add Filter To Transaction Uid
     *
     * @param string $uid
     *
     * @return Collection
     */
    public function addFilterToTransactionUid($uid)
    {
        return $this->addFieldToFilter('main_table.transaction_uid', $uid);
    }

    /**
     * Add Filter To Not Transaction Uid
     *
     * @param string $uid
     *
     * @return Collection
     */
    public function addFilterToNotTransactionUid($uid)
    {
        return $this->addFieldToFilter([
            'main_table.transaction_uid',
            'main_table.transaction_uid',
        ], [
            ['neq' => $uid],
            ['null' => true],
        ]);
    }

    /**
     * Join table to collection select
     *
     * @param string|array $table
     * @param string       $cond
     * @param string|array $cols
     *
     * @return $this
     */
    public function joinLeft($table, $cond, $cols = '*')
    {
        if (is_array($table)) {
            foreach ($table as $k => $v) {
                $alias = $k;
                $table = $v;
                break;
            }
        } else {
            $alias = $table;
        }

        if (!isset($this->_joinedTables[$alias])) {
            $this->getSelect()->joinLeft([$alias => $this->getTable($table)], $cond, $cols);
            $this->_joinedTables[$alias] = true;
        }

        return $this;
    }

    /**
     * Add Filter To WebsiteId
     *
     * @param string $code
     *
     * @return Collection
     */
    public function addFilterToWebsiteId($code)
    {
        return $this->addFieldToFilter('main_table.website_id', $code);
    }

    /**
     * Website Ids
     *
     * @return array
     * @throws LocalizedException
     */
    public function websiteIds()
    {
        $collection = clone $this;
        $collection
            ->removeAllFieldsFromSelect()
            ->removeFieldFromSelect($collection->getResource()->getIdFieldName())
            ->addFieldToSelect('website_id');

        $collection->getSelect()->group('website_id');

        return array_column($collection->getData(), 'website_id');
    }

    /**
     * Clear
     *
     * @return Collection|AbstractCollection
     */
    public function clear()
    {
        $this->_totalRecords = null;

        return parent::clear();
    }

    /**
     * Update Lock
     *
     * @param array  $data
     * @param string $groupCode
     * @param int    $websiteId
     *
     * @return int
     * @throws Exception
     */
    public function updateLock(array $data, string $groupCode, int $websiteId)
    {
        $this->getSelect()->group('identify');
        $queueIds = $this->filterBlockedQueueRecords->execute($this->getAllIds(), $groupCode, $websiteId);

        try {
            if ($queueIds) {
                $this->_conn->beginTransaction();
                foreach (array_chunk($queueIds, self::UPDATE_CHUNK) as $queueIdsChunk) {
                    $this->_conn->update(
                        $this->getMainTable(),
                        $data,
                        $this->_conn->prepareSqlCondition('queue_id', ['in' => $queueIdsChunk])
                    );
                }
                $this->_conn->commit();
            }
        } catch (Exception $e) {
            $this->_conn->rollBack();
            throw $e;
        }

        if ($queueIds) {
            /** @var Queue $queue */
            foreach ($this as $queue) {
                $queue->addData($data);
            }
        }

        return count($queueIds);
    }

    /**
     * @return $this
     */
    public function denyDependentItems()
    {
        $this->joinLeft(
            ['relation' => $this->getTable('tnw_salesforce_entity_queue_relation')],
            'main_table.queue_id = relation.parent_id',
            []
        );

        $this->addFieldToFilter('relation.parent_id', ['null' => true]);

        $this->distinct(true);

        return $this;
    }

    /**
     * @return AbstractCollection|void
     */
    protected function _initSelect()
    {
        $this->addFilterToMap('queue_id', 'main_table.queue_id');

        parent::_initSelect();
    }

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Queue::class, ResourceQueue::class);
    }

    /**
     * Before Add Loaded Item
     *
     * @param DataObject $item
     *
     * @return DataObject
     */
    protected function beforeAddLoadedItem(DataObject $item)
    {
        $this->getResource()->unserializeFields($item);

        return parent::beforeAddLoadedItem($item);
    }
}
