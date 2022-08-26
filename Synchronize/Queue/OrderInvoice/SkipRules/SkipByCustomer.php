<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\OrderInvoice\SkipRules;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip order invoice sync by customer rule.
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
     * @param IsSyncDisabledInterface     $isSyncDisabled
     * @param ResourceConnection $resourceConnection
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
        if (strcasecmp($queue->getEntityLoad(), 'orderInvoice') !== 0) {
            return false;
        }

        $customerId = $this->getCustomerId((int)$queue->getEntityId());
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute($customerId);
    }

    /**
     * @param int $salesInvoiceId
     *
     * @return int
     */
    private function getCustomerId(int $salesInvoiceId): int
    {
        if (isset($this->cache[$salesInvoiceId])) {
            return $this->cache[$salesInvoiceId];
        }

        $connection = $this->resourceConnection->getConnection();
        $orderInvoiceTable = $connection->getTableName('sales_invoice');
        $orderTable = $connection->getTableName('sales_order');
        $select = $connection->select()
            ->from(['main_table' => $orderTable], [OrderInterface::CUSTOMER_ID])
            ->joinInner(
                ['sales_invoice' => $orderInvoiceTable],
                'main_table.entity_id = sales_invoice.order_id AND sales_invoice.entity_id = ' . $salesInvoiceId,
                []
            );
        $this->cache[$salesInvoiceId] = (int)$connection->fetchOne($select);

        return $this->cache[$salesInvoiceId];
    }
}
