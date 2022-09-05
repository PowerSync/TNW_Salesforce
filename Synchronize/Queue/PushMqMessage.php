<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue;

use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Api\MessageQueue\PublisherAdapter;

class PushMqMessage
{
    /** @var PublisherAdapter */
    protected $publisher;

    /** @var StoreManagerInterface */
    protected $storeManager;

    /**
     * PushMqMessage constructor.
     * @param PublisherAdapter $publisher
     */
    public function __construct(
        PublisherAdapter $publisher,
        StoreManagerInterface $storeManager
    ) {
        $this->publisher = $publisher;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $syncType
     * @throws LocalizedException
     */
    public function sendMessage($syncType)
    {
        $websiteId = $this->storeManager->getWebsite()->getId();
        $this->publisher->publish(Add::TOPIC_NAME, (string)$websiteId);
    }

}
