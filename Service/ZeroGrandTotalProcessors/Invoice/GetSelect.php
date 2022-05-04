<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\ZeroGrandTotalProcessors\Invoice;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use TNW\Salesforce\Api\Service\GetSelectInterface;
use TNW\Salesforce\Service\ZeroGrandTotalProcessors\JoinZeroGrandTotalCondition;

/**
 *  Select with filter by zero grand total for invoice
 */
class GetSelect implements GetSelectInterface
{
    /** @var ResourceConnection */
    private $resource;

    /** @var JoinZeroGrandTotalCondition */
    private $joinZeroGrandTotalCondition;

    /**
     * @param ResourceConnection          $resource
     * @param JoinZeroGrandTotalCondition $joinZeroGrandTotalCondition
     */
    public function __construct(
        ResourceConnection $resource,
        JoinZeroGrandTotalCondition $joinZeroGrandTotalCondition
    )
    {
        $this->resource = $resource;
        $this->joinZeroGrandTotalCondition = $joinZeroGrandTotalCondition;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): ?Select
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            ['main_table' => $this->resource->getTableName('sales_invoice')],
            [
                'entity_id' => 'main_table.entity_id'
            ]
        );
        $this->joinZeroGrandTotalCondition->execute($select, 'main_table', 'order_id');
        $select->where('main_table.entity_id IN (?)', $entityIds);

        return $select;
    }
}
