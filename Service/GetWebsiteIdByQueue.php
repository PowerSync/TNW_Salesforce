<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service;

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
     */
    public function execute(Queue $queue): int
    {
        $entityLoad = $queue->getEntityLoad();
        $entityId = $queue->getEntityId();
        if($entityLoad === 'product' && $queue->getObjectType() === 'PricebookEntry') {
            $entityLoad = 'store';
            $entityId = (int)($queue->getEntityLoadAdditional()['store_id'] ?? 0);
        }

        return (int)($this->getWebsiteByEntityLoad->execute([$entityId], (string)$entityLoad)[$entityId] ?? 0);
    }
}
