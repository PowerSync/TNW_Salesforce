<?php
declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Queue\SkipRules;

use Magento\Framework\Exception\NoSuchEntityException;
use TNW\Salesforce\Api\Service\GetIdsFilteredByCustomerGroupConfigurationInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

/**
 * Skip by customer group configuration rule.
 */
class SkipByCustomerGroupConfiguration implements SkipInterface
{
    /** @var GetIdsFilteredByCustomerGroupConfigurationInterface */
    private $getIdsFilteredByCustomerGroupConfiguration;

    /**
     * @param GetIdsFilteredByCustomerGroupConfigurationInterface $getIdsFilteredByCustomerGroupConfiguration
     */
    public function __construct(
        GetIdsFilteredByCustomerGroupConfigurationInterface $getIdsFilteredByCustomerGroupConfiguration
    ) {
        $this->getIdsFilteredByCustomerGroupConfiguration = $getIdsFilteredByCustomerGroupConfiguration;
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     */
    public function apply(Queue $queue): bool
    {
        return $this->needSkip((int)$queue->getEntityId());
    }

    /**
     * @param int $entityId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    private function needSkip(int $entityId): bool
    {
        $serviceResult = $this->getIdsFilteredByCustomerGroupConfiguration->execute([$entityId]);

        return !isset($serviceResult[$entityId]);
    }
}
