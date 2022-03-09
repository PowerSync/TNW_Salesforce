<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\CustomerGroupConfiguration\Shipment;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use TNW\Salesforce\Api\Service\CustomerGroupConfiguration\GetSelectInterface;
use TNW\Salesforce\Service\CustomerGroupConfiguration\GetCustomerGroupIds;

/**
 *  Shipment ids filtered by customer group from store configuration
 */
class GetSelect implements GetSelectInterface
{
    /** @var ResourceConnection */
    private $resource;

    /** @var GetCustomerGroupIds */
    private $getCustomerGroupIds;

    /**
     * @param ResourceConnection  $resource
     * @param GetCustomerGroupIds $getCustomerGroupIds
     */
    public function __construct(
        ResourceConnection    $resource,
        GetCustomerGroupIds   $getCustomerGroupIds
    ) {
        $this->resource = $resource;
        $this->getCustomerGroupIds = $getCustomerGroupIds;
    }

    public function execute(array $entityIds): Select
    {
        $connection = $this->resource->getConnection();
        $select = $connection->select()->from(
            ['sales_shipment' => $this->resource->getTableName('sales_shipment')],
            [
                'entity_id' => 'sales_invoice.entity_id'
            ]
        );
        $select->join(
            ['sales_order' => $this->resource->getTableName('sales_order')],
            'sales_order.entity_id = sales_shipment.order_id',
            []
        );
        $select->where('entity_id IN (?)', $entityIds);
        $customerSyncGroupsIds = $this->getCustomerGroupIds->execute();
        $customerSyncGroupsIds !== null && $select->where('customer_group_id IN (?)', $customerSyncGroupsIds);

        return $select;
    }
}
