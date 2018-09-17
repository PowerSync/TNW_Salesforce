<?php
namespace TNW\Salesforce\Synchronize\Unit\Website;

use TNW\Salesforce\Synchronize;

class Load extends Synchronize\Unit\LoadAbstract
{
    /**
     * @var \Magento\Store\Model\WebsiteFactory
     */
    protected $websiteFactory;

    public function __construct(
        $name,
        array $entities,
        Synchronize\Units $units,
        Synchronize\Group $group,
        Synchronize\Unit\IdentificationInterface $identification,
        \Magento\Store\Model\WebsiteFactory $websiteFactory
    ) {
        parent::__construct($name, $entities, $units, $group, $identification);
        $this->websiteFactory = $websiteFactory;
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
            $website->getResource()->load($website, $entity);
            $entity = $website;
        }

        if (!$entity instanceof \Magento\Store\Model\Website || null === $entity->getId()) {
            throw new \RuntimeException('Unable to load entity');
        }

        return $entity;
    }
}