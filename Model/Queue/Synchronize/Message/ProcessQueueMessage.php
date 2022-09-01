<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Queue\Synchronize\Message;

use Exception;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;
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

    /** @var LoggerInterface */
    private $logger;

    /**
     * ProcessQueueMessage constructor.
     *
     * @param Synchronize                 $synchronizeEntity
     * @param Config                      $salesforceConfig
     * @param WebsiteEmulator             $websiteEmulator
     * @param CleanLocalCacheForInstances $cleanLocalCacheForInstances
     * @param LoggerInterface             $logger
     */
    public function __construct(
        Synchronize                 $synchronizeEntity,
        Config                      $salesforceConfig,
        WebsiteEmulator             $websiteEmulator,
        CleanLocalCacheForInstances $cleanLocalCacheForInstances,
        LoggerInterface             $logger
    ) {
        $this->synchronizeEntity = $synchronizeEntity;
        $this->salesforceConfig = $salesforceConfig;
        $this->websiteEmulator = $websiteEmulator;
        $this->cleanLocalCacheForInstances = $cleanLocalCacheForInstances;
        $this->logger = $logger;
    }

    /**
     * process
     *
     * @param $websiteId
     *
     * @return void
     * @throws LocalizedException
     */
    public function process($websiteId)
    {
        try {
            if (!$this->salesforceConfig->getSalesforceStatus()) {
                return;
            }
            $this->websiteEmulator->wrapEmulationWebsite(
                [$this->synchronizeEntity, 'synchronizeToWebsite'],
                $websiteId
            );
        } catch (Exception $e) {
            $this->logger->critical($e->getMessage());
            throw $e;
        } finally {
            $this->cleanLocalCacheForInstances->execute();
        }
    }
}
