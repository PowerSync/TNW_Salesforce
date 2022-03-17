<?php

namespace TNW\Salesforce\Api\Service;

/**
 * Interface GetIdsFilteredByCustomerGroupConfigurationInterface
 */
interface GetIdsFilteredByCustomerGroupConfigurationInterface
{
    /**
     * Returns filtered ids by customer group configuration
     *
     * @param array $entityIds
     *
     * @return array
     */
    public function execute(array $entityIds): array;
}
