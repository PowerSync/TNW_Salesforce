<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Queue\Synchronize\Message;

use Exception;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Config\WebsiteEmulator;
use TNW\Salesforce\Service\CleanLocalCacheForInstances;
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

    /** @var CleanLocalCacheForInstances */
    private $cleanLocalCacheForInstances;

    /**
     * ProcessQueueMessage constructor.
     *
     * @param Synchronize                 $synchronizeEntity
     * @param Config                      $salesforceConfig
     * @param WebsiteEmulator             $websiteEmulator
     * @param CleanLocalCacheForInstances $cleanLocalCacheForInstances
     */
    public function __construct(
        Synchronize                 $synchronizeEntity,
        Config                      $salesforceConfig,
        WebsiteEmulator             $websiteEmulator,
        CleanLocalCacheForInstances $cleanLocalCacheForInstances
    ) {
        $this->synchronizeEntity = $synchronizeEntity;
        $this->salesforceConfig = $salesforceConfig;
        $this->websiteEmulator = $websiteEmulator;
        $this->cleanLocalCacheForInstances = $cleanLocalCacheForInstances;
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

        $this->cleanLocalCacheForInstances->execute();

        $this->websiteEmulator->wrapEmulationWebsite(
            [$this->synchronizeEntity, 'synchronizeToWebsite'],
            $websiteId
        );
    }
}
