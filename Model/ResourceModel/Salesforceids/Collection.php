<?php
namespace TNW\Salesforce\Model\ResourceModel\Salesforceids;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Queue Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\TNW\Salesforce\Model\SalesforceIds::class, \TNW\Salesforce\Model\ResourceModel\Salesforceids::class);
    }
}
