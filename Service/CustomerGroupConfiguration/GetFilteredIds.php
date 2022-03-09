<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\CustomerGroupConfiguration;

use Magento\Framework\App\ResourceConnection;
use TNW\Salesforce\Api\Service\CustomerGroupConfiguration\GetSelectInterface;
use TNW\Salesforce\Api\Service\GetIdsFilteredByCustomerGroupConfigurationInterface;

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

    /**
     * @param GetSelectInterface $getSelect
     * @param ResourceConnection $resource
     * @param string             $entityType
     */
    public function __construct(
        GetSelectInterface $getSelect,
        ResourceConnection $resource,
        string $entityType
    ) {
        $this->getSelect = $getSelect;
        $this->resource = $resource;
        $this->entityType = $entityType;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): array
    {
        $entityIds = array_map('intval', $entityIds);
        $missedIds = [];
        $entityType = $this->entityType;
        foreach ($entityIds as $entityId) {
            if (!isset($this->processedIds[$entityType][$entityId])) {
                $missedIds[] = $entityId;
                $this->processedIds[$entityType][$entityId] = 1;
            }
        }

        if ($missedIds) {
            $connection = $this->resource->getConnection();
            $loadedEntityIds = $connection->fetchCol($this->getSelect->execute($entityIds));
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
