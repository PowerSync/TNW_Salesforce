<?php
namespace TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class Queue extends AbstractDb
{
    public function _construct()
    {
        $this->_init('tnw_salesforce_queue', 'queue_id');
    }

    protected function _beforeSave(\Magento\Framework\Model\AbstractModel $object)
    {
        //TODO: Save parents
        return parent::_beforeSave($object);
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object)
    {
        //TODO: Save children
        return parent::_afterSave($object);
    }
}
