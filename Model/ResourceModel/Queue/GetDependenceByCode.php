<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Model\ResourceModel\Queue;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Model\QueueFactory;
use TNW\Salesforce\Service\Model\ResourceModel\Queue\GetQueuesByIds;

class GetDependenceByCode
{
    /** @var GetQueuesByIds */
    private $getQueuesByIds;

    /** @var GetDependenceQueueIdsGroupedByCode */
    private $getDependenceQueueIdsGroupedByCode;

    /** @var QueueFactory */
    private $factory;

    /**
     * @param GetQueuesByIds                     $getQueuesByIds
     * @param GetDependenceQueueIdsGroupedByCode $getDependenceQueueIdsGroupedByCode
     * @param QueueFactory                       $factory
     */
    public function __construct(
        GetQueuesByIds                     $getQueuesByIds,
        GetDependenceQueueIdsGroupedByCode $getDependenceQueueIdsGroupedByCode,
        QueueFactory $factory
    ) {
        $this->getQueuesByIds = $getQueuesByIds;
        $this->getDependenceQueueIdsGroupedByCode = $getDependenceQueueIdsGroupedByCode;
        $this->factory = $factory;
    }

    /**
     * @param array  $entityIds
     * @param string $code
     *
     * @return Queue[]
     * @throws LocalizedException
     */
    public function execute(array $entityIds, string $code): array
    {
        if (!$entityIds) {
            return [];
        }

        $queueIdsToLoad = [];
        $dependedQueueIdsGroupedByCode = $this->getDependenceQueueIdsGroupedByCode->execute($entityIds);
        foreach ($entityIds as $entityId) {
            $queueIdToLoad = $dependedQueueIdsGroupedByCode[$entityId][$code][0] ?? '';
            $queueIdToLoad && $queueIdsToLoad[$entityId] = $queueIdToLoad;
        }

        $result = [];
        $queuesByIds = $this->getQueuesByIds->execute($queueIdsToLoad);
        foreach ($entityIds as $entityId) {
            $queueId = $queueIdsToLoad[$entityId] ?? null;
            $dependenceQueueByCode = $this->factory->create();
            if ($queueId) {
                $dependenceQueueByCode = $queuesByIds[$queueId] ?? $this->factory->create();
            }
            $result[$entityId] = $dependenceQueueByCode;
        }

        return $result;
    }
}
