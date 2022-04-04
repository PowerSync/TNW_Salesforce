<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\CustomerGroupConfiguration;

use Magento\Framework\App\ResourceConnection;
use TNW\Salesforce\Api\Service\GetIdsFilteredByCustomerGroupConfigurationInterface;
use TNW\Salesforce\Api\Service\GetSelectInterface;

/**
 *  Load ids filtered by customer group from store configuration
 */
class GetFilteredIds implements GetIdsFilteredByCustomerGroupConfigurationInterface
{
    /** @var int[] */
    private $cache = [];

    /** @var int[] */
    private $processedIds = [];

    /** @var GetSelectInterface */
    private $getSelect;

    /** @var ResourceConnection */
    private $resource;

    /** @var string */
    private $entityType;

    /** @var GetCustomerGroupIds */
    private $getCustomerGroupIds;

    /**
     * @param GetSelectInterface  $getSelect
     * @param ResourceConnection  $resource
     * @param GetCustomerGroupIds $getCustomerGroupIds
     * @param string              $entityType
     */
    public function __construct(
        GetSelectInterface  $getSelect,
        ResourceConnection  $resource,
        GetCustomerGroupIds $getCustomerGroupIds,
        string              $entityType
    ) {
        $this->getSelect = $getSelect;
        $this->resource = $resource;
        $this->getCustomerGroupIds = $getCustomerGroupIds;
        $this->entityType = $entityType;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): array
    {
        $entityIds = array_map('intval', $entityIds);
        $entityType = $this->entityType;
        if ($this->getCustomerGroupIds->execute() === null) {
            $result = [];
            foreach ($entityIds as $entityId) {
                $this->processedIds[$entityType][$entityId] = 1;
                $this->cache[$entityType][$entityId] = $entityId;
                $result[$entityId] = $entityId;
            }

            return $result;
        }

        $missedIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processedIds[$entityType][$entityId])) {
                $missedIds[] = $entityId;
                $this->processedIds[$entityType][$entityId] = 1;
            }
        }

        if ($missedIds) {
            $connection = $this->resource->getConnection();
            $select = $this->getSelect->execute($entityIds);
            $loadedEntityIds = $entityIds;
            if ($select) {
                $loadedEntityIds = $connection->fetchCol($select);
            }
            if ($loadedEntityIds) {
                $loadedEntityIds = array_map('intval', $loadedEntityIds);
                foreach ($loadedEntityIds as $loadedEntityId) {
                    $this->cache[$entityType][$loadedEntityId] = $loadedEntityId;
                }
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            if (isset($this->cache[$entityType][$entityId])) {
                $result[$entityId] = $entityId;
            }
        }

        return $result;
    }
}
