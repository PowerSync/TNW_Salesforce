<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\ZeroGrandTotalProcessors;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;

/**
 *  Join order table and zero grand total condition
 */
class JoinZeroGrandTotalCondition
{
    /** @var ResourceConnection */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(
        ResourceConnection $resource
    ) {
        $this->resource = $resource;
    }

    /**
     * Join order table and zero grand total condition
     *
     * @param Select $select
     * @param string $mainTableAlias
     * @param string $mainTableOrderIdField
     *
     * @return void
     */
    public function execute(
        Select $select,
        string $mainTableAlias,
        string $mainTableOrderIdField
    ): void {
        $formatCondition = 'sales_order.entity_id = %s.%s AND sales_order.grand_total <> 0';
        $condition = sprintf($formatCondition, $mainTableAlias, $mainTableOrderIdField);
        $select->join(
            ['sales_order' => $this->resource->getTableName('sales_order')],
            $condition,
            []
        );
    }
}
