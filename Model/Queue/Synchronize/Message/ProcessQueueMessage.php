<?php
declare(strict_types=1);

namespace TNW\Salesforce\Model\Queue\Synchronize\Message;

use Exception;
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
     * ProcessQueueMessage constructor.
     * @param Synchronize $synchronizeEntity
     * @param WebsiteEmulator $websiteEmulator
     */
    public function __construct(
        Synchronize $synchronizeEntity,
        WebsiteEmulator $websiteEmulator
    ) {
        $this->synchronizeEntity = $synchronizeEntity;
        $this->websiteEmulator = $websiteEmulator;
    }

    /**
     * process
     * @param $message
     * @return void
     * @throws Exception
     */
    public function process($websiteId)
    {
        $this->websiteEmulator->wrapEmulationWebsite(
            [$this->synchronizeEntity, 'synchronizeToWebsite'],
            $websiteId
        );
    }
}
