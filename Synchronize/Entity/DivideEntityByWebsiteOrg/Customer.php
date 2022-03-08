<?php

namespace TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

use Magento\Customer\Model\ResourceModel\Customer\Collection;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
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

    /** @var CustomerConfig  */
    private $customerConfig;

    /** @var StoreManagerInterface  */
    private $storeManager;

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
        $this->collectionFactory = $collectionFactory;
        parent::__construct($config);
        $this->customerConfig = $customerConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Load customer entities
     *
     * @param array $ids
     *
     * @return Collection
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function loadEntities($ids)
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter($collection->getRowIdFieldName(), $ids);
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $useAllGroups = $this->customerConfig->getCustomerAllGroups($websiteId);
        if (!$useAllGroups) {
            $customerSyncGroupsIds = $this->customerConfig->getCustomerSyncGroups($websiteId);
            $customerSyncGroupsIds && $collection->addAttributeToFilter('group_id', ['in' => $customerSyncGroupsIds]);
        }

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
