<?php
namespace TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Queue
 */
class Queue extends AbstractDb
{
    /**
     * Serializable Fields
     *
     * @var array
     */
    protected $_serializableFields = [
        'entity_load_additional' => [[], []],
        'additional_data' => [[], []],
    ];

    /**
     * @var string[][]
     */
    private $dependenceByCode = [];

    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('tnw_salesforce_entity_queue', 'queue_id');
    }

    /**
     * After Save
     *
     * @param \TNW\Salesforce\Model\Queue $object
     * @return AbstractDb
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->saveDependence($object);
        return parent::_afterSave($object);
    }

    /**
     * Merge
     *
     * @param \TNW\Salesforce\Model\Queue $queue
     * @return $this
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function merge(\TNW\Salesforce\Model\Queue $queue)
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getMainTable())
            ->where('code = :code')
            ->where('entity_id = :entity_id')
            ->where('entity_load = :entity_load')
            ->where('entity_load_additional = :entity_load_additional')
            ->where('sync_type = :sync_type')
            ->where('website_id = :website_id')
            ->where('status = ?', 'new');

        $data = $connection->fetchRow($select, [
            'code' => $queue->getCode(),
            'entity_id' => $queue->getEntityId(),
            'entity_load' => $queue->getEntityLoad(),
            'entity_load_additional' => $this->getSerializer()->serialize($queue->getEntityLoadAdditional()),
            'sync_type' => $queue->getSyncType(),
            'website_id' => $queue->getWebsiteId()
        ]);

        if (!empty($data)) {
            $queue->setData($data);
            $this->unserializeFields($queue);
        }

        return $this->save($queue);
    }

    /**
     * Save Dependence
     *
     * @param \TNW\Salesforce\Model\Queue $object
     */
    public function saveDependence($object)
    {
        $data = [];
        foreach (array_map([$this, 'objectId'], $object->getDependence()) as $dependenceId) {
            if (empty($dependenceId)) {
                continue;
            }

            $data[] = [
                'queue_id' => $object->getId(),
                'parent_id' => $dependenceId
            ];
        }

        if (empty($data)) {
            return;
        }

        $this->getConnection()
            ->insertOnDuplicate($this->getTable('tnw_salesforce_entity_queue_relation'), $data);
    }

    /**
     * Object Id
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return mixed
     */
    private function objectId(\Magento\Framework\Model\AbstractModel $object)
    {
        return $object->getId();
    }

    /**
     * Get Dependence By Code
     *
     * @param string $code
     * @return string[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getDependenceByCode($code)
    {
        if (empty($this->dependenceByCode)) {
            $select = $this->getConnection()->select()
                ->from(['childQueue' => $this->getMainTable()], ['childCode' => 'code'])
                ->joinInner(
                    ['relation' => $this->getTable('tnw_salesforce_entity_queue_relation')],
                    'childQueue.queue_id = relation.queue_id',
                    []
                )
                ->joinInner(
                    ['parentQueue' => $this->getMainTable()],
                    'relation.parent_id = parentQueue.queue_id',
                    ['parentCode' => 'code']
                )
                ->distinct();

            foreach ($this->getConnection()->fetchAll($select) as $row) {
                $this->dependenceByCode[$row['childCode']][] = $row['parentCode'];
            }
        }

        if (!isset($this->dependenceByCode[$code])) {
            return [];
        }

        return $this->dependenceByCode[$code];
    }

    /**
     * Load By Child
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param string $code
     * @param int $childId
     * @return Queue
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByChild(\Magento\Framework\Model\AbstractModel $object, $code, $childId)
    {
        return $this->load($object, $this->dependenceIdByCode($childId, $code), $this->getIdFieldName());
    }

    /**
     * Dependence Id By Code
     *
     * @param int $queueId
     * @param string $code
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function dependenceIdByCode($queueId, $code)
    {
        $select = $this->getConnection()->select()
            ->from(['relation' => $this->getTable('tnw_salesforce_entity_queue_relation')], [])
            ->joinInner(
                ['queue' => $this->getMainTable()],
                'relation.parent_id = queue.queue_id',
                ['queue_id']
            )
            ->where('relation.queue_id = :queue_id')
            ->where('queue.code = :code');

        return $this->getConnection()->fetchOne($select, ['queue_id' => $queueId, 'code' => $code]);
    }

    /**
     * Dependence Id By Entity Type
     *
     * @param int $queueId
     * @param string $entityType
     * @return int[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function dependenceIdsByEntityType($queueId, $entityType)
    {
        $select = $this->getConnection()->select()
            ->from(['relation' => $this->getTable('tnw_salesforce_entity_queue_relation')], [])
            ->joinInner(
                ['queue' => $this->getMainTable()],
                'relation.parent_id = queue.queue_id',
                ['queue_id']
            )
            ->where('relation.queue_id = :queue_id')
            ->where('queue.entity_type = :entity_type');

        return $this->getConnection()->fetchCol($select, ['queue_id' => $queueId, 'entity_type' => $entityType]);
    }

    /**
     * Load By Parent
     *
     * @param \Magento\Framework\Model\AbstractModel $object
     * @param string $code
     * @param int $parentId
     * @return Queue
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function loadByParent(\Magento\Framework\Model\AbstractModel $object, $code, $parentId)
    {
        return $this->load($object, $this->childIdByCode($parentId, $code), $this->getIdFieldName());
    }

    /**
     * Child Id By Code
     *
     * @param int $queueId
     * @param string $code
     * @return int
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function childIdByCode($queueId, $code)
    {
        $select = $this->getConnection()->select()
            ->from(['relation' => $this->getTable('tnw_salesforce_entity_queue_relation')], [])
            ->joinInner(
                ['queue' => $this->getMainTable()],
                'relation.queue_id = queue.queue_id',
                ['queue_id']
            )
            ->where('relation.parent_id = :queue_id')
            ->where('queue.code = :code');

        return $this->getConnection()->fetchOne($select, ['queue_id' => $queueId, 'code' => $code]);
    }

    /**
     * Child Ids By Entity Type
     *
     * @param int $queueId
     * @param string $entityType
     * @return int[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function childIdsByEntityType($queueId, $entityType)
    {
        $select = $this->getConnection()->select()
            ->from(['relation' => $this->getTable('tnw_salesforce_entity_queue_relation')], [])
            ->joinInner(
                ['queue' => $this->getMainTable()],
                'relation.queue_id = queue.queue_id',
                ['queue_id']
            )
            ->where('relation.parent_id = :queue_id')
            ->where('queue.entity_type = :entity_type');

        return $this->getConnection()->fetchCol($select, ['queue_id' => $queueId, 'entity_type' => $entityType]);
    }
}
