<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\ResourceModel\Objects\Customer;

use TNW\Salesforce\Model\ResourceModel\Objects\SelectAbstract;

class WebsiteSelect extends SelectAbstract
{
    /**
     * @inheritdoc
     */
    public function build(\Magento\Framework\DB\Select $originalSelect, string $alias)
    {
        return $originalSelect->columns([$alias => 'main_table.website_id']);
    }
}
