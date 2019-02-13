<?php
namespace TNW\Salesforce\Model\ResourceModel\Queue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Queue Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var \TNW\Salesforce\Model\Logger\Processor\UidProcessor
     */
    private $uidProcessor;

    /**
     * Collection constructor.
     * @param \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy
     * @param \Magento\Framework\Event\ManagerInterface $eventManager
     * @param \TNW\Salesforce\Model\Logger\Processor\UidProcessor $uidProcessor
     * @param \Magento\Framework\DB\Adapter\AdapterInterface|null $connection
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb|null $resource
     */
    public function __construct(
        \Magento\Framework\Data\Collection\EntityFactoryInterface $entityFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Data\Collection\Db\FetchStrategyInterface $fetchStrategy,
        \Magento\Framework\Event\ManagerInterface $eventManager,
        \TNW\Salesforce\Model\Logger\Processor\UidProcessor $uidProcessor,
        \Magento\Framework\DB\Adapter\AdapterInterface $connection = null,
        \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource = null
    ) {
        parent::__construct($entityFactory, $logger, $fetchStrategy, $eventManager, $connection, $resource);
        $this->uidProcessor = $uidProcessor;
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
     * Add Filter To Current Uid
     *
     * @return Collection
     */
    public function addFilterToCurrentUid()
    {
        return $this->addFieldToFilter([
            'main_table.transaction_uid',
            'main_table.transaction_uid',
        ], [
            ['neq' => $this->uidProcessor->uid()],
            ['null' => true],
        ]);
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
                    [
                        'status' => $status,
                        'sync_attempt' => new \Zend_Db_Expr('sync_attempt + 1'),
                        'transaction_uid' => $this->uidProcessor->uid()
                    ],
                    $this->_conn->prepareSqlCondition('queue_id', ['in' => $queueIds])
                );

                /** @var \TNW\Salesforce\Model\Queue $queue */
                foreach ($this as $queue) {
                    $queue->addData([
                        'status' => $status,
                        'sync_attempt' => $queue->getSyncAttempt() + 1,
                        'transaction_uid' => $this->uidProcessor->uid()
                    ]);
                }
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
