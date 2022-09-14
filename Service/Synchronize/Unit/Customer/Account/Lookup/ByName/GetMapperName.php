<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Synchronize\Unit\Customer\Account\Lookup\ByName;

use Magento\Framework\Exception\LocalizedException;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Model\ResourceModel\Mapper\CollectionFactory;

class GetMapperName
{
    /** @var CollectionFactory */
    private $mapperCollectionFactory;

    /** @var Mapper */
    private $item;

    /**
     * @param CollectionFactory $mapperCollectionFactory
     */
    public function __construct(
        CollectionFactory $mapperCollectionFactory
    ) {
        $this->mapperCollectionFactory = $mapperCollectionFactory;
    }

    /**
     * @param int $websiteId
     *
     * @return Mapper|null
     * @throws LocalizedException
     * @throws \Zend_Db_Select_Exception
     * @throws \Zend_Db_Statement_Exception
     */
    public function execute(int $websiteId): ?Mapper
    {
        if (!isset($this->item[$websiteId])) {
            $collection = $this->mapperCollectionFactory->create();
            $collection->addObjectToFilter('Account');
            $collection->addFieldToFilter('salesforce_attribute_name', 'Name');
            $collection->applyUniquenessByWebsite($websiteId);
            $dataObject = $collection->fetchItem();
            $dataObject && $this->item[$websiteId] = $dataObject;
        }

        return $this->item[$websiteId] ?? null;
    }
}
