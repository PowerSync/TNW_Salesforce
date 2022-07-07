<?php

namespace TNW\Salesforce\Model\Queue\Synchronize\Message;

use Exception;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\WebsiteEmulator;
use TNW\Salesforce\Synchronize\Queue\Synchronize;

class ProcessQueueMessage
{
    /**
     * @var Synchronize
     */
    protected $synchronizeEntity;

    /**
     * @var WebsiteEmulator
     */
    protected $websiteEmulator;

    /**
     * @var Config
     */
    protected $salesforceConfig;

    /**
     * ProcessQueueMessage constructor.
     * @param Synchronize $synchronizeEntity
     * @param Config $salesforceConfig
     * @param WebsiteEmulator $websiteEmulator
     */
    public function __construct(
        Synchronize $synchronizeEntity,
        Config $salesforceConfig,
        WebsiteEmulator $websiteEmulator
    ) {
        $this->synchronizeEntity = $synchronizeEntity;
        $this->salesforceConfig = $salesforceConfig;
        $this->websiteEmulator = $websiteEmulator;
    }

    /**
     * process
     * @param $websiteId
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function process($websiteId)
    {
        if (!$this->salesforceConfig->getSalesforceStatus()) {
            return;
        }

        $this->websiteEmulator->wrapEmulationWebsite(
            [$this->synchronizeEntity, 'synchronizeToWebsite'],
            $websiteId
        );
    }
}
