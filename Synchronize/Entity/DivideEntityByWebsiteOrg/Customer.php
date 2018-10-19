<?php

namespace TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

use TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

class Customer extends DivideEntityByWebsiteOrg
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Customer constructor.
     * @param \TNW\Salesforce\Model\Config $config
     * @param \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
     */
    public function __construct(
        \TNW\Salesforce\Model\Config $config,
        \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory $collectionFactory
    )
    {
        $this->collectionFactory = $collectionFactory;
        parent::__construct($config);
    }


    /**
     * @param $ids
     * @return \Magento\Customer\Model\ResourceModel\Customer\CollectionFactory|\Magento\Framework\Data\CollectionFactory\AbstractDb
     */
    public function loadEntities($ids)
    {
        $collection = $this->collectionFactory->create();
        $entities = $collection->addFieldToFilter($collection->getRowIdFieldName(), $ids);

        return $entities;
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