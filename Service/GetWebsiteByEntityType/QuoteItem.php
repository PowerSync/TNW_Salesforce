<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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
