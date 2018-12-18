<?php

namespace TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

use TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

class Customer extends DivideEntityByWebsiteOrg
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    private $collectionFactory;

    /**
     * Customer constructor.
     * @param \TNW\Salesforce\Model\Config $config
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     */
    public function __construct(
        \TNW\Salesforce\Model\Config $config,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($config);
    }

    /**
     * @param array $ids
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection
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
        return [$entity->getWebsiteId()];
    }
}
