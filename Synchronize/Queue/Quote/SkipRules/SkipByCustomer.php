<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\Quote\SkipRules;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\Service\Customer\GetCustomerIdByQuoteIdInterface;
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

    /** @var GetCustomerIdByQuoteIdInterface */
    private $getCustomerIdByQuoteId;

    /**
     * @param IsSyncDisabledInterface         $isSyncDisabled
     * @param GetCustomerIdByQuoteIdInterface $getCustomerIdByQuoteId
     */
    public function __construct(
        IsSyncDisabledInterface $isSyncDisabled,
        GetCustomerIdByQuoteIdInterface $getCustomerIdByQuoteId
    ) {
        $this->isSyncDisabled = $isSyncDisabled;
        $this->getCustomerIdByQuoteId = $getCustomerIdByQuoteId;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function apply(Queue $queue)
    {
        if (strcasecmp($queue->getEntityLoad(), 'quote') !== 0) {
            return false;
        }

        $customerId = $this->getCustomerIdByQuoteId->execute((int)$queue->getEntityId());
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute($customerId);
    }
}
