<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\OrderShipment\SkipRules;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip order shipment sync by customer rule.
 */
class SkipByCustomer implements SkipInterface
{
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
        if (strcasecmp($queue->getEntityLoad(), 'orderShipment') !== 0) {
            return false;
        }

        $customerId = $this->getCustomerId((int)$queue->getEntityId());
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute($customerId);
    }

    /**
     * @param int $salesShipmentId
     *
     * @return int
     */
    private function getCustomerId(int $salesShipmentId): int
    {
        if (isset($this->cache[$salesShipmentId])) {
            return $this->cache[$salesShipmentId];
        }

        $connection = $this->resourceConnection->getConnection();
        $shipmentTable = $connection->getTableName('sales_shipment');
        $orderTable = $connection->getTableName('sales_order');
        $select = $connection->select()
            ->from(['main_table' => $orderTable], [OrderInterface::CUSTOMER_ID])
            ->joinInner(
                ['sales_shipment' => $shipmentTable],
                'sales_shipment.order_id = main_table.entity_id AND sales_shipment.entity_id = ' . $salesShipmentId,
                []
            );
        $this->cache[$salesShipmentId] = (int)$connection->fetchOne($select);

        return $this->cache[$salesShipmentId];
    }
}
