<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\Order\SkipRules;

use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Model\ResourceModel\Order as OrderResource;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip order sync by customer rule.
 */
class SkipByCustomer implements SkipInterface
{
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

        $customerId = $this->getCustomerId((int)$queue->getEntityId());
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute($customerId);
    }

    /**
     * @param int $orderId
     *
     * @return int|null
     * @throws LocalizedException
     */
    private function getCustomerId(int $orderId): ?int
    {
        if (isset($this->cache[$orderId])) {
            return $this->cache[$orderId];
        }

        $connection = $this->orderResource->getConnection();
        $table = $this->orderResource->getMainTable();
        $select = $connection->select()
            ->from($table, [OrderInterface::CUSTOMER_ID])
            ->where($connection->quoteInto('entity_id = ?', $orderId));
        $this->cache[$orderId] = (int)$connection->fetchOne($select);

        return $this->cache[$orderId];
    }
}
