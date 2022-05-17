<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\CustomerGroupConfiguration\Invoice;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use TNW\Salesforce\Api\Service\GetSelectInterface;

/**
 *  Invoice ids filtered by customer group from store configuration
 */
class GetSelect implements GetSelectInterface
{
    /** @var ResourceConnection */
    private $resource;

    /**
     * @param ResourceConnection $resource
     */
    public function __construct(ResourceConnection $resource)
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
            ['sales_invoice' => $this->resource->getTableName('sales_invoice')],
            [
                'entity_id' => 'sales_invoice.entity_id'
            ]
        );
        $select->join(
            ['sales_order' => $this->resource->getTableName('sales_order')],
            'sales_order.entity_id = sales_invoice.order_id',
            ['group_id' => 'customer_group_id']
        );
        $select->join(
            ['store' => $this->resource->getTableName('store')],
            'store.store_id = sales_invoice.store_id',
            ['website_id']
        );
        $select->where('sales_invoice.entity_id IN (?)', $entityIds);

        return $select;
    }
}
