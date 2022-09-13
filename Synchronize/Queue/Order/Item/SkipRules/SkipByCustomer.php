<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\Order\Item\SkipRules;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\Skip\PreloadQueuesDataInterface;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip order item sync by customer rule.
 */
class SkipByCustomer implements SkipInterface, CleanableInstanceInterface, PreloadQueuesDataInterface
{
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var IsSyncDisabledInterface */
    private $isSyncDisabled;

    /** @var int[] */
    private $cache;

    /** @var ResourceConnection */
    private $resourceConnection;

    /**
     * @param IsSyncDisabledInterface $isSyncDisabled
     * @param ResourceConnection      $resourceConnection
     */
    public function __construct(IsSyncDisabledInterface $isSyncDisabled, ResourceConnection $resourceConnection)
    {
        $this->isSyncDisabled = $isSyncDisabled;
        $this->resourceConnection = $resourceConnection;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function apply(Queue $queue)
    {
        if (strcasecmp($queue->getEntityLoad(), 'orderItem') !== 0) {
            return false;
        }

        $entityId = (int)$queue->getEntityId();
        $customerId = $this->getCustomerIds([$entityId])[$entityId] ?? null;
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute([$customerId])[$customerId] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->cache = [];
    }

    /**
     * @inheritDoc
     */
    public function preload(array $queues): void
    {
        $orderItemIds = [];
        foreach ($queues as $queue) {
            if (strcasecmp($queue->getEntityLoad(), 'orderItem') !== 0) {
                continue;
            }
            $entityId = (int)$queue->getEntityId();
            $orderItemIds[] = $entityId;
        }

        $customerIdsResult = $this->getCustomerIds($orderItemIds);

        $customerIds = [];
        foreach ($customerIdsResult as $customerId) {
            if ($customerId) {
                $customerIds[] = $customerId;
            }
        }

        $this->isSyncDisabled->execute($customerIds);
    }

    /**
     * @param array $entityIds
     *
     * @return array
     */
    private function getCustomerIds(array $entityIds): array
    {
        if (!$entityIds) {
            return [];
        }

        $entityIds = array_map('intval', $entityIds);
        $entityIds = array_unique($entityIds);

        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->cache[$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->cache[$entityId] = null;
            }
        }

        if ($missedEntityIds) {
            $select = $this->getSelect();
            $connection = $this->resourceConnection->getConnection();
            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('order_item.item_id IN(?)', $missedEntityIdsChunk);

                $items = $connection->fetchAll($batchSelect);
                foreach ($items as $item) {
                    $entityId = (int)($item['entity_id'] ?? 0);
                    $value = (int)($item['value'] ?? 0);
                    $this->cache[$entityId] = $value;
                }
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $this->cache[$entityId] ?? null;
        }

        return $result;
    }

    /**
     * @return Select
     */
    private function getSelect(): Select
    {
        $connection = $this->resourceConnection->getConnection();
        $orderItemTable = $connection->getTableName('sales_order_item');
        $orderTable = $connection->getTableName('sales_order');

        $select = $connection->select();
        $select->from(['main_table' => $orderTable]);
        $select->joinInner(
            ['order_item' => $orderItemTable],
            'order_item.order_id = main_table.entity_id',
            []
        );
        $select->reset(Select::COLUMNS);
        $select->columns(
            [
                'entity_id' => 'order_item.item_id',
                'value' => 'main_table.customer_id',
            ]
        );

        return $select;
    }
}
