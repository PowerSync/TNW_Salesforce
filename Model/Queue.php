<?php
namespace TNW\Salesforce\Model;

use TNW\Salesforce\Model\ResourceModel;

class Queue extends \Magento\Framework\Model\AbstractModel
{
    /**
     * @var Queue[]
     */
    private $dependence = [];

    /**
     *
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Queue::class);
    }

    /**
     * @return string
     */
    public function getCode()
    {
        return $this->_getData('code');
    }

    /**
     * @return string
     */
    public function getEntityType()
    {
        return $this->_getData('entity_type');
    }

    /**
     * @return string
     */
    public function getObjectType()
    {
        return $this->_getData('object_type');
    }

    /**
     * @return int
     */
    public function getEntityId()
    {
        return $this->_getData('entity_id');
    }

    /**
     * @return string
     */
    public function getEntityLoad()
    {
        return $this->_getData('entity_load');
    }

    /**
     * @return string
     */
    public function getEntityLoadAdditional()
    {
        return $this->_getData('entity_load_additional');
    }

    /**
     * @return string
     */
    public function getWebsiteId()
    {
        return $this->_getData('website_id');
    }

    /**
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
     * @return Queue[]
     */
    public function getDependence()
    {
        return $this->dependence;
    }
}
