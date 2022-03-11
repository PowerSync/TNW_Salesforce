<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\Quote\SkipRules;

use Magento\Framework\Exception\LocalizedException;
use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip quote sync by customer rule.
 */
class SkipByCustomer implements SkipInterface
{
    /** @var IsSyncDisabledInterface */
    private $isSyncDisabled;

    /** @var QuoteResource */
    private $quoteResource;

    /** @var int[] */
    private $cache;

    /**
     * @param IsSyncDisabledInterface $isSyncDisabled
     * @param QuoteResource           $quoteResource
     */
    public function __construct(IsSyncDisabledInterface $isSyncDisabled, QuoteResource $quoteResource)
    {
        $this->isSyncDisabled = $isSyncDisabled;
        $this->quoteResource = $quoteResource;
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

        $customerId = $this->getCustomerId($queue->getEntityId());
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute($customerId);
    }

    /**
     * @param int $quoteId
     *
     * @return int|null
     * @throws LocalizedException
     */
    private function getCustomerId(int $quoteId): ?int
    {
        if (isset($this->cache[$quoteId])) {
            return $this->cache[$quoteId];
        }

        $connection = $this->quoteResource->getConnection();
        $table = $this->quoteResource->getMainTable();
        $select = $connection->select()
            ->from($table, ['entity_id'])
            ->where($connection->quoteInto('entity_id = ?', $quoteId));
        $this->cache[$quoteId] = (int)$connection->fetchOne($select);

        return $this->cache[$quoteId];
    }
}
