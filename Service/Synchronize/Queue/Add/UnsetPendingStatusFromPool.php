<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Queue\Add;

use TNW\Salesforce\Model\ResourceModel\Objects;
use TNW\Salesforce\Synchronize\Queue\Unit\CreateQueue\UnsetPendingStatusPool;

/**
 *  Class UnsetPendingStatusFromPool
 */
class UnsetPendingStatusFromPool
{
    /** @var UnsetPendingStatusPool */
    private $pendingStatusPool;

    /** @var Objects */
    private $objects;

    /**
     * @param UnsetPendingStatusPool $pendingStatusPool
     * @param Objects                $objects
     */
    public function __construct(
        UnsetPendingStatusPool $pendingStatusPool,
        Objects                $objects
    ) {
        $this->pendingStatusPool = $pendingStatusPool;
        $this->objects = $objects;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        $items = $this->pendingStatusPool->getItems();
        foreach ($items as $objectType => $entityTypes) {
            foreach ($entityTypes as $entityType => $websiteIds) {
                foreach ($websiteIds as $websiteId => $entityIds) {
                    $entityIds = array_values($entityIds);
                    $objectIds = $this->objects->massLoadObjectIds($entityIds, (string)$entityType, (int)$websiteId);
                    $entityIdsForUpdateStatus = [];
                    foreach ($entityIds as $entityId) {
                        if (count($objectIds[$entityId])) {
                            $entityIdsForUpdateStatus[] = $entityId;
                        }
                    }
                    $this->objects->unsetPendingStatus(
                        $entityIdsForUpdateStatus,
                        $entityType,
                        $websiteId,
                        $objectType
                    );

                }
            }
        }

        $this->pendingStatusPool->clear();
    }
}
