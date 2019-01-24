<?php
namespace TNW\Salesforce\Model\ResourceModel;

use Magento\Framework\DataObject;
use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    /**
     * @var array
     */
    protected $_serializableFields = [
        'entity_load_additional' => [[], []],
        'additional_data' => [[], []],
    ];

    public function _construct()
    {
        $this->_init('tnw_salesforce_entity_queue', 'queue_id');
    }

    /**
     * @param \TNW\Salesforce\Model\Queue $object
     * @return AbstractDb
     */
    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        $this->saveDependence($object);
        return parent::_afterSave($object);
    }

    /**
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
            ->where('website_id = ?', $queue->getWebsiteId())
            ->where('status = ?', 'new')
        ;

        $data = $connection->fetchRow($select);
        if (!empty($data)) {
            $queue->setData(array_merge($data, $queue->getData()));
            $this->unserializeFields($queue);
        }

        return $this->save($queue);
    }

    /**
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
     * @param \Magento\Framework\Model\AbstractModel $object
     * @return mixed
     */
    private function objectId(\Magento\Framework\Model\AbstractModel $object)
    {
        return $object->getId();
    }
}
