<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Model\Queue;

/**
 *  Class GetWebsiteIdByQueue
 */
class GetWebsiteIdByQueue
{
    /** @var GetWebsiteByEntityType */
    private $getWebsiteByEntityLoad;

    /**
     * @param GetWebsiteByEntityType $getWebsiteByEntityLoad
     */
    public function __construct(
        GetWebsiteByEntityType $getWebsiteByEntityLoad
    )
    {
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
        $entityLoad = $queue->getEntityLoad();
        $entityId = $queue->getEntityId();
        if ($entityLoad === 'product' && $queue->getObjectType() === 'PricebookEntry') {
            $entityLoad = 'store';
            $entityId = (int)($queue->getEntityLoadAdditional()['store_id'] ?? 0);
        }
        if ($entityLoad === 'TierPrice') {
            $entityId = (int)($queue->getEntityLoadAdditional()['website_id'] ?? 0);
        }

        return (int)($this->getWebsiteByEntityLoad->execute([$entityId], (string)$entityLoad)[$entityId] ?? 0);
    }
}
