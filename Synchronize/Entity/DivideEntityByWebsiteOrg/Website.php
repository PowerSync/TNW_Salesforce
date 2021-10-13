<?php
declare(strict_types=1);

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
     * @return mixed
     * @throws \Magento\Framework\Exception\LocalizedException
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
     * @return array
     */
    public function getEntityWebsiteIds($entity): array
    {
        return [$entity->getId()];
    }

}
