<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

/**
 *  QuoteItem websites
 */
class QuoteItem extends ByStoreIdAbstract
{
    protected $entityField = 'item_id';

    /**
     * @inheritDoc
     */
    protected function getMainTable(): string
    {
        return 'quote_item';
    }
}
