<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\Order\Item\SkipRules;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip order item sync by customer rule.
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
        if (strcasecmp($queue->getEntityLoad(), 'orderItem') !== 0) {
            return false;
        }

        $customerId = $this->getCustomerId((int)$queue->getEntityId());
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute($customerId);
    }

    /**
     * @param int $orderItemId
     *
     * @return int
     */
    private function getCustomerId(int $orderItemId): int
    {
        if (isset($this->cache[$orderItemId])) {
            return $this->cache[$orderItemId];
        }

        $connection = $this->resourceConnection->getConnection();
        $orderItemTable = $connection->getTableName('sales_order_item');
        $orderTable = $connection->getTableName('sales_order');
        $select = $connection->select()
            ->from(['main_table' => $orderTable], [OrderInterface::CUSTOMER_ID])
            ->joinInner(
                ['order_item' => $orderItemTable],
                'order_item.order_id = main_table.entity_id AND order_item.order_id = ' . $orderItemId,
                []
            );
        $this->cache[$orderItemId] = (int)$connection->fetchOne($select);

        return $this->cache[$orderItemId];
    }
}
