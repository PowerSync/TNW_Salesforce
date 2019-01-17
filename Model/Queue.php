<?php
namespace TNW\Salesforce\Model;

use TNW\Salesforce\Model\ResourceModel;

class Queue extends \Magento\Framework\Model\AbstractModel
{
    protected function _construct()
    {
        $this->_init(ResourceModel\Queue::class);
    }
}
