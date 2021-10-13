<?php
declare(strict_types=1);

namespace TNW\Salesforce\Api;

/**
 * Website interface
 */
interface WebsiteInterface
{
    /**
     * Do Magento Websites synchronization to Salesforce custom object
     *
     * @param \Magento\Store\Model\Website[] $websites
     */
    public function syncWebsites($websites);
}
