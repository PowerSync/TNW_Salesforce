<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

/**
 *  Quote websites
 */
class Quote extends ByStoreIdAbstract
{
    /**
     * @inheritDoc
     */
    protected function getMainTable(): string
    {
        return 'quote';
    }
}
