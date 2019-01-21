<?php
namespace TNW\Salesforce\Model;

use TNW\Salesforce\Model\ResourceModel;

class Queue extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\Queue::class);
    }

    public function getEntityType()
    {
        return $this->_getData('entity_type');
    }

    public function getEntityId()
    {
        return $this->_getData('entity_id');
    }

    public function getEntityLoad()
    {
        return $this->_getData('entity_load');
    }

    /**
     * @param Queue[] $parents
     */
    public function setParents(array $parents)
    {

    }

    /**
     * @return Queue[]
     */
    public function getParents()
    {

    }

    /**
     * @param Queue[] $children
     */
    public function setChildren(array $children)
    {

    }

    /**
     * @return Queue[]
     */
    public function getChildren()
    {

    }
}
