<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace TNW\Salesforce\Model\ResourceModel;

use Magento\Framework\App\ResourceConnection;

class FilterBlockedQueueRecords
{

    /**
     * @var ResourceConnection
     */
    private $resourceConnection;

    /**
     * @var string[]
     */
    private $processStatuses;

    /**
     * @var string[]
     */
    private $errorStatuses;

    /**
     * @var string[]
     */
    private $newStatuses;

    /**
     * @param ResourceConnection $resourceConnection
     * @param string[] $processStatuses
     * @param string[] $errorStatuses
     * @param string[] $newStatuses
     */
    public function __construct(
        ResourceConnection $resourceConnection,
        array              $processStatuses = [],
        array              $errorStatuses = [],
        array              $newStatuses = []
    ) {
        $this->resourceConnection = $resourceConnection;
        $this->processStatuses = $processStatuses;
        $this->errorStatuses = $errorStatuses;
        $this->newStatuses = $newStatuses;
    }

    /**
     * @param array $queueIds
     * @return array
     */
    public function execute(array $queueIds): array
    {
        if (!empty($queueIds)) {
            $connection = $this->resourceConnection->getConnection();
            $dependentFilter = $connection->select()
                ->from(
                    ['parent' => $this->resourceConnection->getTableName('tnw_salesforce_entity_queue')],
                    []
                )
                ->joinInner(
                    ['relation' => $this->resourceConnection->getTableName('tnw_salesforce_entity_queue_relation')],
                    'relation.parent_id = parent.queue_id',
                    ['queue_id']
                )
                ->joinInner(
                    ['child' => $this->resourceConnection->getTableName('tnw_salesforce_entity_queue')],
                    'relation.queue_id = child.queue_id',
                    []
                )
                ->where('parent.status IN (?)', array_merge($this->processStatuses, $this->errorStatuses, $this->newStatuses))
                ->where('child.queue_id IN (?)', $queueIds)
                ->group('relation.queue_id');

            $blockedIds = $connection->fetchCol($dependentFilter);
            if (!empty($blockedIds)) {
                $queueIds = array_diff($queueIds, $blockedIds);
            }
        }

        return $queueIds;
    }
}
