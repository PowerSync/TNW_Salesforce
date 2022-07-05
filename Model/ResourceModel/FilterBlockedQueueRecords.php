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
    private const PAGE_SIZE = 100;

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
     * @param string $groupCode
     * @param int $websiteId
     * @return array
     */
    public function execute(array $queueIds, string $groupCode, int $websiteId): array
    {
        if (!empty($queueIds)) {
            $connection = $this->resourceConnection->getConnection();
            $dependentFilter = $connection->select()
                ->from(
                    ['parent' => $this->resourceConnection->getTableName('tnw_salesforce_entity_queue')], [])
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
                ->where('parent.website_id = ?', $websiteId)
                ->where('child.code = ?', $groupCode);

            //  Not 0
            $pageNumber = 1;
            while (true) {
                $dependentFilter->limitPage($pageNumber, self::PAGE_SIZE);
                $blockedIds = $connection->fetchCol($dependentFilter);
                if (empty($blockedIds)) {
                    break;
                }
                $queueIds = array_diff($queueIds, $blockedIds);
                $pageNumber++;
            }
        }

        return $queueIds;
    }
}
