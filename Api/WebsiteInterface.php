<?php
namespace TNW\Salesforce\Api;

/**
 * Website interface
 */
interface WebsiteInterface
{
    /**
     * Do Magento Websites syncronization to Salesforce custom object
     *
     * @param \Magento\Store\Model\Website[] $websites
     */
    public function syncWebsites($websites);
}
