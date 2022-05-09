<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

/**
 *  Get shipment websites
 */
class Shipment extends ByStoreIdAbstract
{
    /**
     * @inheritDoc
     */
    protected function getMainTable(): string
    {
        return 'sales_shipment';
    }
}
