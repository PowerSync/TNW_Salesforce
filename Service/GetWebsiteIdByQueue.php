<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\Skip\PreloadQueuesDataInterface;

/**
 *  Class GetWebsiteIdByQueue
 */
class GetWebsiteIdByQueue implements PreloadQueuesDataInterface
{
    /** @var GetWebsiteByEntityType */
    private $getWebsiteByEntityLoad;

    /**
     * @param GetWebsiteByEntityType $getWebsiteByEntityLoad
     */
    public function __construct(
        GetWebsiteByEntityType $getWebsiteByEntityLoad
    ) {
        $this->getWebsiteByEntityLoad = $getWebsiteByEntityLoad;
    }

    /**
     * @param Queue $queue
     *
     * @return int
     * @throws LocalizedException
     */
    public function execute(Queue $queue): int
    {
        [$entityLoad, $entityId] = $this->getDataFromQueue($queue);

        return (int)($this->getWebsiteByEntityLoad->execute([$entityId], (string)$entityLoad)[$entityId] ?? 0);
    }

    /**
     * @inheritDoc
     */
    public function preload(array $queues): void
    {
        $preloadItems = [];
        foreach ($queues as $queue) {
            [$entityLoad, $entityId] = $this->getDataFromQueue($queue);
            $preloadItems[$entityLoad][] = $entityId;
        }

        foreach ($preloadItems as $entityLoad => $entityIds) {
            $this->getWebsiteByEntityLoad->execute($entityIds, (string)$entityLoad);
        }
    }

    /**
     * @param Queue $queue
     *
     * @return array
     */
    private function getDataFromQueue(Queue $queue): array
    {
        $entityLoad = $queue->getEntityLoad();
        $entityId = $queue->getEntityId();
        if ($entityLoad === 'product' && $queue->getObjectType() === 'PricebookEntry') {
            $entityLoad = 'store';
            $entityId = (int)($queue->getEntityLoadAdditional()['store_id'] ?? 0);
        }
        if ($entityLoad === 'TierPrice') {
            $entityId = (int)($queue->getEntityLoadAdditional()['website_id'] ?? 0);
        }

        return [$entityLoad, $entityId];
    }
}
