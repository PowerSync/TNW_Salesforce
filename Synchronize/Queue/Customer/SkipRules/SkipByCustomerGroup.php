<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\Customer\SkipRules;

use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\Skip\PreloadQueuesDataInterface;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Rules which allow or disallow adding customer entity to synchronization queue.
 */
class SkipByCustomerGroup implements SkipInterface,PreloadQueuesDataInterface
{
    private const CUSTOMER = 'customer';

    /**
     * @var SkipInterface[]
     */
    private $skipRulesByQueueLoadFromEntityType;

    /**
     * @param array $skipRulesByQueueLoadFromEntityType
     */
    public function __construct(
        array $skipRulesByQueueLoadFromEntityType
    ) {
        $this->skipRulesByQueueLoadFromEntityType = $skipRulesByQueueLoadFromEntityType;
    }

    /**
     * @inheritDoc
     */
    public function apply(Queue $queue)
    {
        $skipRule = $this->skipRulesByQueueLoadFromEntityType[$queue->getEntityLoad()]
            ?? $this->skipRulesByQueueLoadFromEntityType[self::CUSTOMER];

        return $skipRule->apply($queue);
    }

    /**
     * @inheritDoc
     */
    public function preload(array $queues): void
    {
        $queuesByKey = [];
        foreach ($queues as $queue) {
            $key = isset($this->skipRulesByQueueLoadFromEntityType[$queue->getEntityLoad()]) ? $queue->getEntityLoad() : self::CUSTOMER;
            $queuesByKey[$key][] = $queue;
        }

        foreach ($queuesByKey as $key => $groupedQueues) {
            $skipRule = $this->skipRulesByQueueLoadFromEntityType[$key] ?? null;
            if ($skipRule instanceof PreloadQueuesDataInterface) {
                $skipRule->preload($groupedQueues);
            }
        }
    }
}
