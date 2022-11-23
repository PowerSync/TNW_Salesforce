<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Grid;

use TNW\Salesforce\Model\Queue;

class UpdateGridsByQueues
{
    /** @var GetGridUpdatersByEntityTypes */
    private $getGridUpdatersByEntityTypes;

    /**
     * @param GetGridUpdatersByEntityTypes $getGridUpdatersByEntityTypes
     */
    public function __construct(
        GetGridUpdatersByEntityTypes $getGridUpdatersByEntityTypes
    )
    {
        $this->getGridUpdatersByEntityTypes = $getGridUpdatersByEntityTypes;
    }

    /**
     * @param Queue[] $queues
     *
     * @return void
     */
    public function execute(array $queues): void
    {
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
