<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\Order\SkipRules;

use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use TNW\Salesforce\Api\ChunkSizeInterface;
use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\Skip\PreloadQueuesDataInterface;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip order sync by customer rule.
 */
class SkipByCustomer implements SkipInterface, CleanableInstanceInterface, PreloadQueuesDataInterface
{
    private const CHUNK_SIZE = ChunkSizeInterface::CHUNK_SIZE;

    /** @var IsSyncDisabledInterface */
    private $isSyncDisabled;

    /** @var OrderResource */
    private $orderResource;

    /** @var int[] */
    private $cache;

    /**
     * @param IsSyncDisabledInterface $isSyncDisabled
     * @param OrderResource           $orderResource
     */
    public function __construct(IsSyncDisabledInterface $isSyncDisabled, OrderResource $orderResource)
    {
        $this->isSyncDisabled = $isSyncDisabled;
        $this->orderResource = $orderResource;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function apply(Queue $queue)
    {
        if (strcasecmp($queue->getEntityLoad(), 'order') !== 0) {
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
        $orderIds = [];
        foreach ($queues as $queue) {
            if (strcasecmp($queue->getEntityLoad(), 'order') !== 0) {
                continue;
            }
            $entityId = (int)$queue->getEntityId();
            $orderIds[] = $entityId;
        }

        $customerIdsResult = $this->getCustomerIds($orderIds);

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
     * @throws LocalizedException
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
            $connection = $this->orderResource->getConnection();
            foreach (array_chunk($missedEntityIds, self::CHUNK_SIZE) as $missedEntityIdsChunk) {
                $batchSelect = clone $select;
                $batchSelect->where('entity_id IN(?)', $missedEntityIdsChunk);

                $items = $connection->fetchAll($batchSelect);
                foreach ($items as $item) {
                    $entityId = $item['entity_id'];
                    $value = $item['customer_id'];
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
     * @throws LocalizedException
     */
    private function getSelect(): Select
    {
        $connection = $this->orderResource->getConnection();
        $table = $this->orderResource->getMainTable();
        $select = $connection->select();
        $select->from($table, [OrderInterface::CUSTOMER_ID, OrderInterface::ENTITY_ID]);

        return $select;
    }
}
