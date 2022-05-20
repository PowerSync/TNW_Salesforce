<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\CustomerGroupConfiguration\Quote;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Select;
use TNW\Salesforce\Api\Service\GetSelectInterface;

/**
 *  Quote ids filtered by customer group from store configuration
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
            [$this->resource->getTableName('quote')],
            [
                'entity_id',
                'group_id' => 'customer_group_id'
            ]
        );
        $select->join(
            ['store' => $this->resource->getTableName('store')],
            'store.store_id = quote.store_id',
            ['website_id']
        );
        $select->where('entity_id IN (?)', $entityIds);

        return $select;
    }
}
