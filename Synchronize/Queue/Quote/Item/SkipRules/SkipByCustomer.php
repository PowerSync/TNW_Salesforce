<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\Quote\Item\SkipRules;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip quote item sync by customer rule.
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
        if (strcasecmp($queue->getEntityLoad(), 'quoteItem') !== 0) {
            return false;
        }

        $customerId = $this->getCustomerId((int)$queue->getEntityId());
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute($customerId);
    }

    /**
     * @param int $quoteItemId
     *
     * @return int
     */
    private function getCustomerId(int $quoteItemId): int
    {
        if (isset($this->cache[$quoteItemId])) {
            return $this->cache[$quoteItemId];
        }

        $connection = $this->resourceConnection->getConnection();
        $quoteItemTable = $connection->getTableName('quote_item');
        $quoteTable = $connection->getTableName('quote');
        $select = $connection->select()
            ->from(['main_table' => $quoteTable], ['customer_id'])
            ->joinInner(
                ['quote_item' => $quoteItemTable],
                'quote_item.quote_id = main_table.entity_id AND quote_item.quote_id = ' . $quoteItemId,
                []
            );
        $this->cache[$quoteItemId] = (int)$connection->fetchOne($select);

        return $this->cache[$quoteItemId];
    }
}
