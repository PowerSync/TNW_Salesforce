<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

/**
 *  Get order item websites.
 */
class OrderItem extends ByStoreIdAbstract
{
    /**
     * @var string
     */
    protected $entityField = 'item_id';

    /**
     * @inheritDoc
     */
    protected function getMainTable(): string
    {
        return 'sales_order_item';
    }
}
