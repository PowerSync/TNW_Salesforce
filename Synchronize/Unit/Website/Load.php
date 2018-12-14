<?php
namespace TNW\Salesforce\Synchronize\Unit\Website;

use TNW\Salesforce\Synchronize;

class Load extends Synchronize\Unit\LoadAbstract
{
    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    private $websiteFactory;

    /**
     * @var \Magento\Store\Model\ResourceModel\Website
     */
    private $resourceWebsite;

    /**
     * Load constructor.
     *
     * @param string $name
     * @param array $entities
     * @param Synchronize\Units $units
     * @param Synchronize\Group $group
     * @param Synchronize\Unit\IdentificationInterface $identification
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject
     * @param \Magento\Store\Model\WebsiteFactory $websiteFactory
     * @param \Magento\Store\Model\ResourceModel\Website $resourceWebsite
     */
    public function __construct(
        $name,
        array $entities,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $entityObject,
        \Magento\Store\Model\WebsiteFactory $websiteFactory,
        \Magento\Store\Model\ResourceModel\Website $resourceWebsite
    ) {
        parent::__construct($name, $entities, $units, $group, $identification, $entityObject);
        $this->websiteFactory = $websiteFactory;
        $this->resourceWebsite = $resourceWebsite;
    }

    /**
     * {@inheritdoc}
     */
    public function description()
    {
        return __('Loading Magento Websites ...');
    }

    /**
     * @param mixed $entity
     * @return \Magento\Store\Model\Website
     */
    public function loadEntity($entity)
    {
        //TODO: Костыль
        if ($entity instanceof \Magento\Store\Model\Website) {
            $entity = $entity->getId();
        }

        if (is_numeric($entity)) {
            $website = $this->websiteFactory->create();
            $this->resourceWebsite->load($website, $entity);
            $entity = $website;
        }

        if (!$entity instanceof \Magento\Store\Model\Website || null === $entity->getId()) {
            throw new \RuntimeException('Unable to load entity');
        }

        return $entity;
    }
}
