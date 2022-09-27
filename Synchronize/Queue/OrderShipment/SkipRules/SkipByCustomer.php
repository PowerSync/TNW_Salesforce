<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\OrderShipment\SkipRules;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\Skip\PreloadQueuesDataInterface;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip order shipment sync by customer rule.
 */
class SkipByCustomer implements SkipInterface, CleanableInstanceInterface, PreloadQueuesDataInterface
{
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var IsSyncDisabledInterface */
    private $isSyncDisabled;

    /** @var int[] */
    private $cache = [];

    /** @var array  */
    private $processed = [];

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
        if (strcasecmp($queue->getEntityLoad(), 'orderShipment') !== 0) {
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
        $this->processed = [];
    }

    /**
     * @inheritDoc
     */
    public function preload(array $queues): void
    {
        $entityIds = [];
        foreach ($queues as $queue) {
            if (strcasecmp($queue->getEntityLoad(), 'orderShipment') !== 0) {
                continue;
            }
            $entityId = (int)$queue->getEntityId();
            $entityIds[] = $entityId;
        }

        $customerIdsResult = $this->getCustomerIds($entityIds);

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
            if (!isset($this->processed[$entityId])) {
                $missedEntityIds[] = $entityId;
                $this->cache[$entityId] = null;
                $this->processed[$entityId] = 1;
            }
        }

        if ($missedEntityIds) {
            $select = $this->getSelect();
            $connection = $this->resourceConnection->getConnection();
            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('sales_shipment.entity_id IN(?)', $missedEntityIdsChunk);

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
        $shipmentTable = $connection->getTableName('sales_shipment');
        $orderTable = $connection->getTableName('sales_order');
        $select = $connection->select()
            ->from(['main_table' => $orderTable], [OrderInterface::CUSTOMER_ID])
            ->joinInner(
                ['sales_shipment' => $shipmentTable],
                'sales_shipment.order_id = main_table.entity_id',
                []
            );
        $select->reset(Select::COLUMNS);
        $select->columns(
            [
                'entity_id' => 'sales_shipment.entity_id',
                'value' => 'main_table.customer_id',
            ]
        );

        return $select;
    }
}
