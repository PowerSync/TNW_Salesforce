<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\CustomerGroupConfiguration\Customer;

use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\DB\Select;
use TNW\Salesforce\Api\Service\GetSelectInterface;

/**
 *  Customer ids filtered by customer group from store configuration
 */
class GetSelect implements GetSelectInterface
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param CollectionFactory   $collectionFactory
     */
    public function __construct(CollectionFactory $collectionFactory)
    {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): ?Select
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter($collection->getRowIdFieldName(), $entityIds);
        $select = $collection->getSelect();
        $select->reset(Select::COLUMNS);
        $select->columns(
            [
                $collection->getRowIdFieldName() => 'entity_id',
                'group_id',
                'website_id'
            ]
        );

        return $select;
    }
}
