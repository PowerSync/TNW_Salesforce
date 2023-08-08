<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\MessageQueue;

use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Api\MessageQueue\PublisherAdapter;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Prequeue\Process;
use TNW\Salesforce\Synchronize\Queue\Add;

/**
 *  Put poison pill and restart consumers
 */
class CheckMemoryLimit
{
    /**
     * @var Config
     */
    protected $salesforceConfig;

    /** @var PublisherAdapter  */
    protected PublisherAdapter $publisher;

    /** @var StoreManagerInterface  */
    protected StoreManagerInterface $storeManager;

    /**
     * @param Config $salesforceConfig
     */
    public function __construct(
        Config $salesforceConfig,
        PublisherAdapter $publisher,
        StoreManagerInterface $storeManager
    ) {
        $this->salesforceConfig = $salesforceConfig;
        $this->publisher = $publisher;
        $this->storeManager = $storeManager;
    }

    /**
     * @return PublisherAdapter
     */
    public function getPublisher(): PublisherAdapter
    {
        return $this->publisher;
    }

    /**
     * @return StoreManagerInterface
     */
    public function getStoreManager(): StoreManagerInterface
    {
        return $this->storeManager;
    }

    /**
     * @return void
     */
    public function publishMessages()
    {
        $this->publisher->publish(Process::MQ_TOPIC_NAME, false);
        foreach ($this->storeManager->getWebsites(true) as $website) {
            $this->publisher->publish(Add::TOPIC_NAME, (string) $website->getId());
        }
    }

    /**
     * @return void
     */
    public function exit()
    {
        $this->publishMessages();
        // Emergency exit from the specific consumer
        // phpcs:ignore
        exit(0); // @codingStandardsIgnoreLine
    }

    /**
     * Put poison pill
     *
     * @return void
     */
    public function execute(): void
    {
        $memory = memory_get_usage(true);
        if ($memory > $this->salesforceConfig->getMemoryLimitByte()) {
           $this->exit();
        }
    }
}
