<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Queue\SkipRules;

use TNW\Salesforce\Api\Service\GetIdsFilteredByCustomerGroupConfigurationInterface;
use TNW\Salesforce\Model\Queue;
use TNW\Salesforce\Synchronize\Queue\SkipInterface;

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
     * @param Queue $queue
     *
     * @return bool
     */
    public function apply(Queue $queue): bool
    {
        return $this->needSkip((int)$queue->getEntityId());
    }

    private function needSkip(int $entityId): bool
    {
        $serviceResult = $this->getIdsFilteredByCustomerGroupConfiguration->execute([$entityId]);

        return !isset($serviceResult[$entityId]);
    }
}
