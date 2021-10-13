<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model\ResourceModel\PreQueue;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Queue Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'prequeue_id';

    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(\TNW\Salesforce\Model\PreQueue::class, \TNW\Salesforce\Model\ResourceModel\PreQueue::class);
    }
}
