<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\CustomerGroupConfiguration\Customer;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\DB\Select;
use TNW\Salesforce\Api\Service\GetSelectInterface;
use TNW\Salesforce\Service\CustomerGroupConfiguration\GetCustomerGroupIds;

/**
 *  Customer ids filtered by customer group from store configuration
 */
class GetSelect implements GetSelectInterface
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var GetCustomerGroupIds */
    private $getCustomerGroupIds;

    /**
     * @param CollectionFactory   $collectionFactory
     * @param GetCustomerGroupIds $getCustomerGroupIds
     */
    public function __construct(
        CollectionFactory     $collectionFactory,
        GetCustomerGroupIds $getCustomerGroupIds
    ) {
        $this->collectionFactory = $collectionFactory;
        $this->getCustomerGroupIds = $getCustomerGroupIds;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): ?Select
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter($collection->getRowIdFieldName(), $entityIds);
        $customerSyncGroupsIds = $this->getCustomerGroupIds->execute();
        $customerSyncGroupsIds !== null && $collection->addAttributeToFilter('group_id', ['in' => $customerSyncGroupsIds]);
        $select = $collection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->columns(
            [
                $collection->getRowIdFieldName()
            ]
        );

        return $select;
    }
}
