<?php
namespace TNW\Salesforce\Model\ResourceModel\Queue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Queue Collection
 */
class Collection extends AbstractCollection
{
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
    public function addFilterToSyncType($syncType)
    {
        return $this->addFieldToFilter('main_table.sync_type', $syncType);
    }

    /**
     * Add Filter To Code
     *
     * @param string $code
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
     * @return Collection
     */
    public function addFilterToStatus($code)
    {
        return $this->addFieldToFilter('main_table.status', $code);
    }

    /**
     * Add Filter Dependent
     *
     * @return $this
     */
    public function addFilterDependent()
    {
        return $this
            ->joinLeft(
                ['relation' => 'tnw_salesforce_entity_queue_relation'],
                'main_table.queue_id = relation.queue_id',
                []
            )
            ->joinLeft(
                ['dependent' => 'tnw_salesforce_entity_queue'],
                'relation.parent_id = dependent.queue_id',
                []
            )
            ->addFieldToFilter('dependent.status', [['null' => true], ['in' => ['complete']]]);
    }

    /**
     * Join table to collection select
     *
     * @param string|array $table
     * @param string $cond
     * @param string|array $cols
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
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * Set All Status
     *
     * @param string $status
     * @return Collection
     * @throws \Exception
     */
    public function updateAllStatus($status)
    {
        $this->_conn->beginTransaction();
        try {
            $this->_select->forUpdate();
            $queueIds = $this->walk('getId');
            $this->_select->forUpdate(false);

            if (!empty($queueIds)) {
                $this->_conn->update(
                    $this->getMainTable(),
                    ['status' => $status],
                    $this->_conn->prepareSqlCondition('queue_id', ['in' => $queueIds])
                );

                $this->each('setStatus', [$status]);
            }

            $this->_conn->commit();
        } catch (\Exception $e) {
            $this->_conn->rollBack();
            throw $e;
        }

        return $this;
    }

    /**
     * Before Add Loaded Item
     *
     * @param \Magento\Framework\DataObject $item
     * @return \Magento\Framework\DataObject
     */
    protected function beforeAddLoadedItem(\Magento\Framework\DataObject $item)
    {
        $this->getResource()->unserializeFields($item);
        return parent::beforeAddLoadedItem($item);
    }
}
