<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\Customer;

use Magento\Quote\Model\ResourceModel\Quote as QuoteResource;
use TNW\Salesforce\Api\Service\Customer\GetCustomerIdByQuoteIdInterface;

/**
 * Get customer id by quote id service.
 */
class GetCustomerIdByQuoteId implements GetCustomerIdByQuoteIdInterface
{
    /** @var QuoteResource */
    private $quoteResource;

    /** @var int[] */
    private $cache;

    /**
     * @param QuoteResource $quoteResource
     */
    public function __construct(QuoteResource $quoteResource)
    {
        $this->quoteResource = $quoteResource;
    }

    /**
     * @inheritDoc
     */
    public function execute(int $quoteId): ?int
    {
        if (isset($this->cache[$quoteId])) {
            return $this->cache[$quoteId];
        }

        $connection = $this->quoteResource->getConnection();
        $table = $this->quoteResource->getMainTable();
        $select = $connection->select()
            ->from($table, ['customer_id'])
            ->where($connection->quoteInto('entity_id = ?', $quoteId));
        $this->cache[$quoteId] = (int)$connection->fetchOne($select);

        return $this->cache[$quoteId];
    }
}
