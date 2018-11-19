<?php

namespace TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

use function PHPSTORM_META\type;
use TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

class Website extends DivideEntityByWebsiteOrg
{
    /**
     * @var \Magento\Store\Model\ResourceModel\Website\CollectionFactory
     */
    protected $collectionFactory;

    /**
     * Customer constructor.
     * @param \TNW\Salesforce\Model\Config $config
     * @param \Magento\Store\Model\ResourceModel\Website\CollectionFactory $collectionFactory
     */
    public function __construct(
        \TNW\Salesforce\Model\Config $config,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory $collectionFactory
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
        foreach ($ids as $data) {
            if (is_object($data)) {
                return $ids;
            }
        }

        $collection = $this->collectionFactory->create();

        $entities = $collection->addFieldToFilter($collection->getResource()->getIdFieldName(), $ids);

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