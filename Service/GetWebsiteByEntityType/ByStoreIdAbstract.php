<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Api\Service\GetWebsiteByEntityType\GetWebsiteIdByEntityIdsInterface;

/**
 *  Get website ids for entities by store id field
 */
abstract class ByStoreIdAbstract implements GetWebsiteIdByEntityIdsInterface
{

    /** @var ResourceConnection */
    private $resource;

    /** @var StoreManagerInterface */
    private $storeManager;

    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /**
     * @param ResourceConnection    $resource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ResourceConnection    $resource,
        StoreManagerInterface $storeManager
    ) {
        $this->resource = $resource;
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     *
     * @throws NoSuchEntityException
     */
    public function execute(array $entityIds): array
    {
        $missedEntityIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processed[$entityId])) {
                $this->processed[$entityId] = 1;
                $missedEntityIds[] = $entityId;
            }
        }

        if ($missedEntityIds) {
            $connection = $this->resource->getConnection();
            $select = $connection->select()->from(
                ['main_table' => $this->resource->getTableName($this->getMainTable())],
                ['entity_id', 'store_id']
            );
            $select->where('entity_id IN (?)', $missedEntityIds);
            $items = $connection->fetchPairs($select);
            foreach ($missedEntityIds as $missedEntityId) {
                $storeId = $items[$missedEntityId] ?? null;
                $websiteId = 0;
                if ($storeId) {
                    $websiteId = (int)$this->storeManager->getStore($storeId)->getWebsiteId();
                }

                $this->cache[$missedEntityId] = $websiteId;
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $this->cache[$entityId] ?? 0;
        }

        return $result;
    }

    /**
     * Get main table name
     *
     * @return string
     */
    abstract protected function getMainTable(): string;
}
