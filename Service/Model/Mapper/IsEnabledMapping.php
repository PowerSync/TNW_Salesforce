<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\Model\Mapper;

use TNW\Salesforce\Api\CleanableInstanceInterface;
use TNW\Salesforce\Model\Mapper;
use TNW\Salesforce\Model\ResourceModel\Mapper\CollectionFactory;

class IsEnabledMapping implements CleanableInstanceInterface
{
    /** @var array */
    private $cache = [];

    /** @var array */
    private $processed = [];

    /** @var CollectionFactory */
    private $collectionFactory;

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @param string $objectType
     * @param string $magentoAttributeName
     *
     * @return bool
     */
    public function execute(string $objectType, string $magentoAttributeName): bool
    {
        if (isset($this->processed[$objectType][$magentoAttributeName])) {
            return $this->cache[$objectType][$magentoAttributeName] ?? false;
        }

        $this->processed[$objectType][$magentoAttributeName] = 1;
        $collection = $this->collectionFactory->create();
        $collection->addObjectToFilter($objectType);
        $collection->addFieldToFilter('magento_attribute_name', ['eq' => $magentoAttributeName]);

        /** @var Mapper $mapping */
        $mapping = $collection->getFirstItem();
        $enabled = (bool)$mapping->getData('magento_to_sf_when');
        $this->cache[$objectType][$magentoAttributeName] = $enabled;

        return $enabled;
    }

    /**
     * @inheritDoc
     */
    public function clearLocalCache(): void
    {
        $this->cache = [];
        $this->processed = [];
    }
}
