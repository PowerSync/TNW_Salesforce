<?php

namespace  TNW\Salesforce\Model\ResourceModel\Customer\Mapper;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init('TNW\Salesforce\Model\Customer\Mapper', 'TNW\Salesforce\Model\ResourceModel\Mapper');
    }
}
