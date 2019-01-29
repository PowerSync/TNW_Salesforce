<?php
namespace TNW\Salesforce\Model\ResourceModel;

use Magento\Framework\DataObject;
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
            ->where('code = ?', $queue->getCode())
            ->where('entity_id = ?', $queue->getEntityId())
            ->where('entity_load = ?', $queue->getEntityLoad())
            ->where('entity_load_additional = ?', $this->getSerializer()->serialize($queue->getEntityLoadAdditional()))
            ->where('sync_type = ?', $queue->getSyncType())
            ->where('website_id = ?', $queue->getWebsiteId())
            ->where('status = ?', 'new')
        ;

        $data = $connection->fetchRow($select);
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
}
