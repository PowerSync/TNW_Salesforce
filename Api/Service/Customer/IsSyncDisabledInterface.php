<?php
namespace TNW\Salesforce\Api\Service\Customer;

use Magento\Framework\Exception\LocalizedException;

/**
 * Is customer sync disabled service interface.
 */
interface IsSyncDisabledInterface
{
    /**
     * Return true if customer sync is disabled.
     *
     * @param int $customerId
     *
     * @return bool
     * @throws LocalizedException
     */
    public function execute(int $customerId): bool;
}
