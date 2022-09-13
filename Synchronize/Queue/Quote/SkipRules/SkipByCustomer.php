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
use TNW\Salesforce\Synchronize\Queue\Skip\PreloadQueuesDataInterface;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip quote sync by customer rule.
 */
class SkipByCustomer implements SkipInterface, PreloadQueuesDataInterface
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
        IsSyncDisabledInterface         $isSyncDisabled,
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

        $entityId = (int)$queue->getEntityId();
        $customerId = $this->getCustomerIdByQuoteId->execute([$entityId])[$entityId] ?? null;
        if (!$customerId) {
            return false;
        }

        return $this->isSyncDisabled->execute([$customerId])[$customerId] ?? false;
    }

    /**
     * @inheritDoc
     */
    public function preload(array $queues): void
    {
        $entityIds = [];
        foreach ($queues as $queue) {
            if (strcasecmp($queue->getEntityLoad(), 'quote') !== 0) {
                continue;
            }
            $entityId = (int)$queue->getEntityId();
            $entityIds[] = $entityId;
        }

        $customerIdsResult = $this->getCustomerIdByQuoteId->execute($entityIds);

        $customerIds = [];
        foreach ($customerIdsResult as $customerId) {
            if ($customerId) {
                $customerIds[] = $customerId;
            }
        }

        $this->isSyncDisabled->execute($customerIds);
    }
}
