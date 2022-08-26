<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\ZeroGrandTotalProcessors\Order;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use TNW\Salesforce\Api\Service\GetSelectInterface;

/**
 *  Select with filter by zero grand total for order
 */
class GetSelect implements GetSelectInterface
{
    /** @var ResourceConnection */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    )
    {
        $this->resource = $resource;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): ?Select
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            [$this->resource->getTableName('sales_order')],
            [
                'entity_id'
            ]
        );
        $select->where('entity_id IN (?)', $entityIds);
        $select->where('grand_total <> ?', 0);

        return $select;
    }
}
