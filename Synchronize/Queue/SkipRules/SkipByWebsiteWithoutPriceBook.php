<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\SkipRules;

use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Service\GetWebsiteIdByQueue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 *  Skip when website don`t have price book
 */
class SkipByWebsiteWithoutPriceBook implements SkipInterface, \TNW\Salesforce\Synchronize\Queue\Skip\PreloadQueuesDataInterface
{
    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GetWebsiteIdByQueue
     */
    private $getWebsiteIdByQueue;

    /**
     * @param StoreManagerInterface $storeManager
     * @param GetWebsiteIdByQueue $getWebsiteIdByQueue
     */
    public function __construct(
        StoreManagerInterface $storeManager,
        GetWebsiteIdByQueue $getWebsiteIdByQueue
    ) {
        $this->storeManager = $storeManager;
        $this->getWebsiteIdByQueue = $getWebsiteIdByQueue;
    }

    /**
     * @inheritDoc
     */
    public function apply(Queue $queue): bool
    {
        $needSkip = false;
        $websiteId = $this->getWebsiteIdByQueue->execute($queue);
        if ($websiteId) {
            $website = $this->storeManager->getWebsite($websiteId);
            if ($website && !$website->getData('default_pricebook')) {
                $needSkip = true;
            }
        }

        return $needSkip;
    }

    /**
     * @inheritDoc
     */
    public function preload(array $queues): void
    {
        $this->getWebsiteIdByQueue->preload($queues);
    }
}
