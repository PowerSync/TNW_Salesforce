<?php
declare(strict_types=1);

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
