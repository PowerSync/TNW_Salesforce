<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model\ResourceModel\Queue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Queue Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'queue_id';

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
        $this->_init(\TNW\Salesforce\Model\Queue::class, \TNW\Salesforce\Model\ResourceModel\Queue::class);
    }

    /**
     * Add Filter To Sync Type
     *
     * @param int $syncType
     * @return Collection
     */
    public function addFilterToSyncType($syncType): Collection
    {
        return $this->addFieldToFilter('main_table.sync_type', $syncType);
    }

    /**
     * Add Filter To Code
     *
     * @param string $code
     * @return Collection
     */
    public function addFilterToCode($code): Collection
    {
        return $this->addFieldToFilter('main_table.code', $code);
    }

    /**
     * Add Filter To Code
     *
     * @param string $code
     * @return Collection
     */
    public function addFilterToStatus($code): Collection
    {
        return $this->addFieldToFilter('main_table.status', $code);
    }

    /**
     * Add Filter To Transaction Uid
     *
     * @param string $uid
     * @return Collection
     */
    public function addFilterToTransactionUid($uid): Collection
    {
        return $this->addFieldToFilter('main_table.transaction_uid', $uid);
    }

    /**
     * Add Filter To Not Transaction Uid
     *
     * @param string $uid
     * @return Collection
     */
    public function addFilterToNotTransactionUid($uid): Collection
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
     * Add Filter Dependent
     *
     * @return $this
     */
    public function addFilterDependent(): Collection
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from(['queue' => $this->getTable('tnw_salesforce_entity_queue')], [])
            ->joinInner(
                ['relation' => $this->getTable('tnw_salesforce_entity_queue_relation')],
                'relation.parent_id = queue.queue_id',
                ['queue_id']
            )
            ->where('queue.status NOT IN (?)', ['complete', 'skipped']);

        return $this->addFieldToFilter('queue_id', ['nin' => $select]);
    }

    /**
     * Join table to collection select
     *
     * @param string|array $table
     * @param string $cond
     * @param string|array $cols
     * @return $this
     */
    public function joinLeft($table, $cond, $cols = '*'): Collection
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
     * @return Collection
     */
    public function addFilterToWebsiteId($code): Collection
    {
        return $this->addFieldToFilter('main_table.website_id', $code);
    }

    /**
     * Website Ids
     *
     * @return array
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function websiteIds(): array
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
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function updateLock(array $data): int
    {
        $this->getSelect()->group('identify');

        $this->_conn->beginTransaction();
        try {
//            $this->_select->forUpdate();
            $queueIds = $this->getAllIds();
//            $this->_select->forUpdate(false);

            if (!empty($queueIds)) {
                $this->_conn->update(
                    $this->getMainTable(),
                    $data,
                    $this->_conn->prepareSqlCondition('queue_id', ['in' => $queueIds])
                );

                /** @var \TNW\Salesforce\Model\Queue $queue */
                foreach ($this as $queue) {
                    $queue->addData($data);
                }
            }

            $this->_conn->commit();
        } catch (\Exception $e) {
            $this->_conn->rollBack();
            throw $e;
        }

        return count($queueIds);
    }

    /**
     * Before Add Loaded Item
     *
     * @param \Magento\Framework\DataObject $item
     * @return \Magento\Framework\DataObject
     */
    protected function beforeAddLoadedItem(\Magento\Framework\DataObject $item): \Magento\Framework\DataObject
    {
        $this->getResource()->unserializeFields($item);
        return parent::beforeAddLoadedItem($item);
    }

    /**
     * @return $this
     */
    public function denyDependentItems(): Collection
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
}
