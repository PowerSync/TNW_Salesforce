<?php

namespace TNW\Salesforce\Model\Queue\Synchronize\Message;

use TNW\Salesforce\Synchronize\Queue\Synchronize;

class ProcessQueueMessage
{
    /**
     * @var Synchronize
     */
    protected $synchronizeEntity;

    /**
     * @var \TNW\Salesforce\Model\Config\WebsiteEmulator
     */
    protected $websiteEmulator;

    /**
     * ProcessQueueMessage constructor.
     * @param Synchronize $synchronizeEntity
     * @param \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
     */
    public function __construct(
        Synchronize $synchronizeEntity,
        \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
    )
    {
        $this->synchronizeEntity = $synchronizeEntity;
        $this->websiteEmulator = $websiteEmulator;
    }

    /**
     * process
     * @param $message
     * @return void
     * @throws \Exception
     */
    public function process($websiteId)
    {
        $this->websiteEmulator->wrapEmulationWebsite(
            [$this->synchronizeEntity, 'synchronizeToWebsite'],
            $websiteId
        );
    }
}
