<?php
namespace TNW\Salesforce\Model;

use TNW\Salesforce\Model\ResourceModel;

class Queue extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\Queue::class);
    }

    public function getMagentoType()
    {
        return $this->_getData('magento_type');
    }

    public function getMagentoId()
    {
        return $this->_getData('magento_id');
    }

    public function getMagentoLoad()
    {
        return $this->_getData('magento_load');
    }

    /**
     * @return Queue|null
     */
    public function getParent()
    {
        //TODO: get Parent
    }

    /**
     * @param Queue $queue
     * @return Queue
     */
    public function setParent($queue)
    {
        return $this->setData('parent_id', $queue->getId());
    }
}
