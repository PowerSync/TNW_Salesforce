<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\Customer\SkipRules;

use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Rules which allow or disallow adding customer entity to synchronization queue.
 */
class SkipByCustomerGroup implements SkipInterface
{
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
            ?? $this->skipRulesByQueueLoadFromEntityType['customer'];

        return $skipRule->apply($queue);
    }
}
