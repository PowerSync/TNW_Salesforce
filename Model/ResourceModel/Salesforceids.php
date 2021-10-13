<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Prequeue
 */
class Salesforceids extends AbstractDb
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('tnw_salesforce_objects', 'id');
    }
}
