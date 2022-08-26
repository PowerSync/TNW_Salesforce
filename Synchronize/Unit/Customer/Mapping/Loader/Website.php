<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Customer\Mapping\Loader;

/**
 * Mapping Loader Website
 */
class Website extends \TNW\Salesforce\Synchronize\Unit\EntityLoaderAbstract
{
    /**
     * @var \Magento\Store\Model\StoreManager
     */
    private $storeManager;

    /**
     * Website constructor.
     * @param \Magento\Store\Model\StoreManager $storeManager
     * @param \TNW\Salesforce\Model\Entity\SalesforceIdStorage|null $salesforceIdStorage
     */
    public function __construct(
        \Magento\Store\Model\StoreManager $storeManager,
        \TNW\Salesforce\Model\Entity\SalesforceIdStorage $salesforceIdStorage = null
    ) {
        parent::__construct($salesforceIdStorage);
        $this->storeManager = $storeManager;
    }

    /**
     * Load
     *
     * @param \Magento\Customer\Model\Customer $entity
     * @return \Magento\Framework\Model\AbstractModel
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function load($entity)
    {
        return $this->storeManager->getWebsite($entity->getWebsiteId());
    }
}
