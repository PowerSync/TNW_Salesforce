<?php
namespace TNW\Salesforce\Model;

use TNW\Salesforce\Model\ResourceModel;

/**
 * Class Queue
 *
 * @method \TNW\Salesforce\Model\ResourceModel\Queue _getResource()
 */
class SalesforceIds extends \Magento\Framework\Model\AbstractModel
{
    /**
     * Construct
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Salesforceids::class);
    }
}
