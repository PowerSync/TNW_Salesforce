<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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
     * @param array $entityIds
     *
     * @return array
     * @throws LocalizedException
     */
    public function execute(array $entityIds): array;
}
