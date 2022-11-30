<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Grid;

use TNW\Salesforce\Model\CleanLocalCache\CleanableObjectsList;
use TNW\Salesforce\Model\Queue;

class UpdateGridsByQueues
{
    /** @var GetGridUpdatersByEntityTypes */
    private $getGridUpdatersByEntityTypes;

    /** @var CleanableObjectsList */
    private $cleanableExecutorsList;

    /**
     * @param GetGridUpdatersByEntityTypes $getGridUpdatersByEntityTypes
     * @param CleanableObjectsList         $cleanableExecutorsList
     */
    public function __construct(
        GetGridUpdatersByEntityTypes $getGridUpdatersByEntityTypes,
        CleanableObjectsList $cleanableExecutorsList
    )
    {
        $this->getGridUpdatersByEntityTypes = $getGridUpdatersByEntityTypes;
        $this->cleanableExecutorsList = $cleanableExecutorsList;
    }

    /**
     * @param Queue[] $queues
     *
     * @return void
     */
    public function execute(array $queues): void
    {
        foreach ($this->cleanableExecutorsList->getList() as $item) {
            $item->clearLocalCache();
        }

        $entityIdsByEntityType = [];
        foreach ($queues as $queue) {
            $entityType = $queue->getEntityType();
            $entityId = $queue->getEntityId();
            $entityIdsByEntityType[$entityType][] = $entityId;
        }

        $gridUpdatersByEntityType = $this->getGridUpdatersByEntityTypes->execute();
        foreach ($entityIdsByEntityType as $entityType => $entityIds) {
            $gridUpdaters = $gridUpdatersByEntityType[$entityType] ?? [];
            if ($gridUpdaters) {
                foreach ($gridUpdaters as $gridUpdater) {
                    $gridUpdater->execute($entityIds);
                }
            }
        }
    }
}
