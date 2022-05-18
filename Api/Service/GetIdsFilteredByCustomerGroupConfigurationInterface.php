<?php
namespace TNW\Salesforce\Api\Service;

use Magento\Framework\Exception\NoSuchEntityException;

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
     * @throws NoSuchEntityException
     */
    public function execute(array $entityIds): array;
}
