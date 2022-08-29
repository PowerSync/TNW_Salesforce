<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

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
        GetSelectInterface $getSelect,
        ResourceConnection $resource,
        GetCustomerGroupIds $getCustomerGroupIds,
        string $entityType
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
        $missedIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processedIds[$entityType][$entityId])) {
                $missedIds[] = $entityId;
                $this->processedIds[$entityType][$entityId] = 1;
            }
        }

        if (!$missedIds) {
            return $this->getDataFromCache($entityType, $entityIds);
        }

        $data = $this->getEntitiesData($missedIds);
        $groupedData = $this->groupByWebsite($data);
        foreach ($groupedData as $websiteId => $entities) {
            $allowedCustomerGroups = $this->getCustomerGroupIds->execute($websiteId == '' ? null : $websiteId);
            if ($allowedCustomerGroups === null) {
                $this->addDataToCache($entityType, $entities);
                continue;
            }

            $filteredEntities = $this->filterByGroup($entities, $allowedCustomerGroups);
            $this->addDataToCache($entityType, $filteredEntities);
        }

        return $this->getDataFromCache($entityType, $entityIds);
    }

    /**
     * Filter entities data by customer groups.
     *
     * @param array $entitiesData
     * @param array $allowedCustomerGroups
     *
     * @return array
     */
    private function filterByGroup(array $entitiesData, array $allowedCustomerGroups): array
    {
        foreach ($entitiesData as $key => $data) {
            $currentGroupId = $data['group_id'] ?? null;
            if (!in_array($currentGroupId, $allowedCustomerGroups, false)) {
                unset($entitiesData[$key]);
            }
        }

        return $entitiesData;
    }

    /**
     * Retrieve data by provided select.
     *
     * @param $entityIds
     *
     * @return array
     */
    private function getEntitiesData($entityIds): array
    {
        $connection = $this->resource->getConnection();
        $select = $this->getSelect->execute($entityIds);
        if (!$select) {
            return [];
        }

        return $connection->fetchAll($select);
    }

    /**
     * Group entities data by website id.
     *
     * @param array $entitiesData
     *
     * @return array
     */
    private function groupByWebsite(array $entitiesData): array
    {
        $groupedData = [];
        foreach ($entitiesData as $data) {
            $websiteId = $data['website_id'] ?? null;
            $groupedData[$websiteId][] = $data;
        }

        return $groupedData;
    }

    /**
     * Retrieve data from cache by entity ids.
     *
     * @param string $entityType
     * @param array  $entityIds
     *
     * @return array
     */
    private function getDataFromCache(string $entityType, array $entityIds): array
    {
        $result = [];
        foreach ($entityIds as $entityId) {
            if (isset($this->cache[$entityType][$entityId])) {
                $result[$entityId] = $entityId;
            }
        }

        return $result;
    }

    /**
     * Add entities to local cache.
     *
     * @param string $entityType
     * @param array  $data
     */
    private function addDataToCache(string $entityType, array $data): void
    {
        foreach ($data as $entityData) {
            $entityId = $entityData['entity_id'] ?? null;
            $this->processedIds[$entityType][$entityId] = 1;
            $this->cache[$entityType][$entityId] = $entityId;
        }
    }
}
