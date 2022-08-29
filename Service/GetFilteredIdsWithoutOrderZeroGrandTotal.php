<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service;

use Magento\Framework\App\ResourceConnection;
use RuntimeException;
use TNW\Salesforce\Api\Service\GetSelectInterface;

/**
 *  Filter ids by select
 */
class GetFilteredIdsWithoutOrderZeroGrandTotal
{
    /** @var GetSelectInterface[] */
    private $getSelectProcessors;

    private $processedIds = [];

    /** @var ResourceConnection */
    private $resource;

    /** @var array */
    private $cache = [];

    /**
     * @param ResourceConnection $resource
     * @param array              $getSelectProcessors
     */
    public function __construct(
        ResourceConnection $resource,
        array              $getSelectProcessors
    ) {
        $this->resource = $resource;
        $this->getSelectProcessors = $getSelectProcessors;
    }

    /**
     * Filter ids by select
     */
    public function execute(array $entityIds, string $type): array
    {
        $entityIds = array_map('intval', $entityIds);
        $entityIds = array_unique($entityIds);
        if (!$entityIds) {
            return [];
        }

        $getSelect = $this->getSelectProcessors[$type] ?? null;
        if (!$getSelect) {
            $format = 'Missing getSelect service for type %s';
            throw new RuntimeException(
                sprintf(
                    $format,
                    $type
                )
            );
        }

        $missedIds = [];
        foreach ($entityIds as $entityId) {
            if (!isset($this->processedIds[$type][$entityId])) {
                $missedIds[] = $entityId;
                $this->processedIds[$type][$entityId] = 1;
            }
        }

        if ($missedIds) {
            $connection = $this->resource->getConnection();
            $select = $getSelect->execute($entityIds);
            $loadedEntityIds = $entityIds;
            if ($select) {
                $loadedEntityIds = $connection->fetchCol($select);
            }
            if ($loadedEntityIds) {
                $loadedEntityIds = array_map('intval', $loadedEntityIds);
                foreach ($loadedEntityIds as $loadedEntityId) {
                    $this->cache[$type][$loadedEntityId] = $loadedEntityId;
                }
            }
        }

        $result = [];
        foreach ($entityIds as $entityId) {
            if (isset($this->cache[$type][$entityId])) {
                $result[$entityId] = $entityId;
            }
        }

        return $result;
    }
}
