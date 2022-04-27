<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Salesforce\Model\ResourceModel;

use \Magento\Framework\Model\ResourceModel\Db\AbstractDb;

/**
 * Class Mapper
 */
class Mapper extends AbstractDb
{
    /**
     * Construct
     */
    public function _construct()
    {
        $this->_init('tnw_salesforce_mapper', 'map_id');
    }
}
