<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Model\Customer\Config as CustomerConfig;
use TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

class Customer extends DivideEntityByWebsiteOrg
{
    /**
     * @var CollectionFactory
     */
    private $collectionFactory;

    /**
     * Customer constructor.
     *
     * @param Config                $config
     * @param CollectionFactory     $collectionFactory
     * @param CustomerConfig        $customerConfig
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Config $config,
        CollectionFactory $collectionFactory,
        CustomerConfig $customerConfig,
        StoreManagerInterface $storeManager
    ) {
        parent::__construct($config);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Load customer entities
     *
     * @param array $ids
     *
     * @return Collection
     */
    public function loadEntities($ids)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter($collection->getRowIdFieldName(), $ids);

        return $collection;
    }

    /**
     * @param $entity \Magento\Customer\Model\Customer
     *
     * @return array
     */
    public function getEntityWebsiteIds($entity)
    {
        return [(int)$entity->getWebsiteId()];
    }
}
