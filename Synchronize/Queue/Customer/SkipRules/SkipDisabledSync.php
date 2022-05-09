<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\Customer\SkipRules;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Api\Service\Customer\IsSyncDisabledInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Disabled customer sync skip rule.
 */
class SkipDisabledSync implements SkipInterface
{
    /** @var IsSyncDisabledInterface */
    private $isSyncDisabled;

    /**
     * @param IsSyncDisabledInterface $isSyncDisabled
     */
    public function __construct(IsSyncDisabledInterface $isSyncDisabled)
    {
        $this->isSyncDisabled = $isSyncDisabled;
    }

    /**
     * @inheritDoc
     *
     * @throws LocalizedException
     */
    public function apply(Queue $queue)
    {
        if (strcasecmp($queue->getEntityLoad(), 'customer') !== 0) {
            return false;
        }

        $customerId = (int)$queue->getEntityId();

        return $this->isSyncDisabled->execute($customerId);
    }
}
