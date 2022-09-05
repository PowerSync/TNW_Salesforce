<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

/**
 *  Get order websites
 */
class Order extends ByStoreIdAbstract
{
    /**
     * @inheritDoc
     */
    protected function getMainTable(): string
    {
        return 'sales_order';
    }
}
