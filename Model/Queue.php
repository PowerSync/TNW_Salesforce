<?php
namespace TNW\Salesforce\Model;

use TNW\Salesforce\Model\ResourceModel;

/**
 * Class Queue
 */
class Queue extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var Queue[]
     */
    private $dependence = [];

    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Queue::class);
    }

    /**
     * Code
     *
     * @return string
     */
    public function getCode()
    {
        return $this->_getData('code');
    }

    /**
     * Entity Type
     *
     * @return string
     */
    public function getEntityType()
    {
        return $this->_getData('entity_type');
    }

    /**
     * Object Type
     *
     * @return string
     */
    public function getObjectType()
    {
        return $this->_getData('object_type');
    }

    /**
     * Entity Id
     *
     * @return int
     */
    public function getEntityId()
    {
        return $this->_getData('entity_id');
    }

    /**
     * Entity Load
     *
     * @return string
     */
    public function getEntityLoad()
    {
        return $this->_getData('entity_load');
    }

    /**
     * Entity Load Additional
     *
     * @return array
     */
    public function getEntityLoadAdditional()
    {
        return $this->_getData('entity_load_additional');
    }

    /**
     * Sync Type
     *
     * @return mixed
     */
    public function getSyncType()
    {
        return $this->_getData('sync_type');
    }

    /**
     * Website Id
     *
     * @return string
     */
    public function getWebsiteId()
    {
        return $this->_getData('website_id');
    }

    /**
     * Set Dependence
     *
     * @param Queue[] $queues
     * @return Queue
     */
    public function setDependence(array $queues)
    {
        $this->_hasDataChanges = true;
        $this->dependence = $queues;
        return $this;
    }

    /**
     * Add Dependence
     *
     * @param Queue $queue
     * @return Queue
     */
    public function addDependence($queue)
    {
        $this->_hasDataChanges = true;
        $this->dependence[] = $queue;
        return $this;
    }

    /**
     * Get Dependence
     *
     * @return Queue[]
     */
    public function getDependence()
    {
        return $this->dependence;
    }
}
