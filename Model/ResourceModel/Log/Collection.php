<?php
declare(strict_types=1);

namespace  TNW\Salesforce\Model\ResourceModel\Log;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use TNW\Salesforce\Model;

class Collection extends AbstractCollection
{
    /**
     * Resource initialization
     *
     * @return void
     */
    protected function _construct()
    {
        $this->_init(Model\Log::class, Model\ResourceModel\Log::class);
    }
}
