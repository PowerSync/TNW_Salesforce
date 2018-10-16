<?php

namespace TNW\Salesforce\Synchronize\Entity\DevideEntityByWebsiteOrg;

use TNW\Salesforce\Synchronize\Entity\DevideEntityByWebsiteOrg;

class Website extends DevideEntityByWebsiteOrg
{
    /**
     * @var \Magento\Store\Model\ResourceModel\Website\Collection
     */
    protected $collection;

    /**
     * Customer constructor.
     * @param \TNW\Salesforce\Model\Config $config
     * @param \Magento\Store\Model\ResourceModel\Website\Collection $collection
     */
    public function __construct(
        \TNW\Salesforce\Model\Config $config,
        \Magento\Store\Model\ResourceModel\Website\Collection $collection
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
        $entities = $this->collection->addFieldToFilter($this->collection->getIdFieldName(), $ids);

        return $entities;
    }

    /**
     * @param $entity \Magento\Store\Model\Website
     *
     * @return mixed
     */
    public function getEntityWebsiteIds($entity)
    {
        return [$entity->getId()];
    }

}