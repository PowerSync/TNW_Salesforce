<?php

namespace TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

use TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

class Customer extends DivideEntityByWebsiteOrg
{
    /**
     * @var \Magento\Customer\Model\ResourceModel\Customer\Collection
     */
    protected $collection;

    /**
     * Customer constructor.
     * @param \TNW\Salesforce\Model\Config $config
     * @param \Magento\Customer\Model\ResourceModel\Customer\Collection $collection
     */
    public function __construct(
        \TNW\Salesforce\Model\Config $config,
        \Magento\Customer\Model\ResourceModel\Customer\Collection $collection
    )
    {
        $this->collection = $collection;
        parent::__construct($config);
    }


    /**
     * @param $ids
     * @return \Magento\Customer\Model\ResourceModel\Customer\Collection|\Magento\Framework\Data\Collection\AbstractDb
     */
    public function loadEntities($ids)
    {
        $entities = $this->collection->addFieldToFilter($this->collection->getRowIdFieldName(), $ids);

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