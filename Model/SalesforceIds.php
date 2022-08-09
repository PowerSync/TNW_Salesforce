<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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
