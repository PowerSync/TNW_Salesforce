<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

use Magento\Framework\App\ResourceConnection;
use TNW\Salesforce\Api\Service\GetWebsiteByEntityType\GetWebsiteIdByEntityIdsInterface;

/**
 *  Get customers website
 */
class Customer implements GetWebsiteIdByEntityIdsInterface
{
    /** @var ResourceConnection */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection    $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): array
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $this->resource->getTableName('customer_entity')],
            ['entity_id', 'website_id']
        );
        $select->where('entity_id IN (?)', $entityIds);

        $items = $connection->fetchPairs($select);

        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $items[$entityId] ?? 0;
        }

        return $result;
    }
}
