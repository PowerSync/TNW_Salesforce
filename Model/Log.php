<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model;

use Magento\Framework\Model\AbstractModel;

class Log extends AbstractModel
{
    /**
     * @return void
     */
    protected function _construct()
    {
        $this->_init(ResourceModel\Log::class);
    }
}
