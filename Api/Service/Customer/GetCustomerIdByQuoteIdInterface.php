<?php
namespace TNW\Salesforce\Api\Service\Customer;

use Magento\Framework\Exception\LocalizedException;

/**
 * Get customer id by quote id service interface.
 */
interface GetCustomerIdByQuoteIdInterface
{
    /**
     * Retrieve customer id.
     *
     * @param int $quoteId
     *
     * @return int|null
     * @throws LocalizedException
     */
    public function execute(int $quoteId): ?int;
}
