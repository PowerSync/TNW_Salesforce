<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Mapping\Loader\Website;

use Magento\Store\Model\ResourceModel\Website\CollectionFactory;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\LoaderInterface;

class Loader implements LoaderInterface
{
    /** @var CollectionFactory */
    private $collectionFactory;

    /** @var array  */
    private $processed = [];

    /** @var array  */
    private $cache = [];

    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        CollectionFactory $collectionFactory
    ) {
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entities): array
    {
        $websiteIds = [];
        foreach ($entities as $entity) {
            $websiteIds[] = $entity->getWebsiteId();
        }

        if(!$websiteIds) {
            return [];
        }

        $websiteIds = array_unique($websiteIds);
        $missedWebsiteIds = [];
        foreach ($websiteIds as $websiteId) {
            if(!isset($this->processed[$websiteId])) {
                $missedWebsiteIds[] = $websiteId;
                $this->processed[$websiteId] = 1;
            }
        }

        if ($missedWebsiteIds) {
            $collection = $this->collectionFactory->create();
            $idFieldName = $collection->getResource()->getIdFieldName();
            $collection->addFieldToFilter($idFieldName, ['in' => $missedWebsiteIds]);
            foreach ($collection as $item) {
                $this->cache[$item->getId()] = $item;
            }
        }

        $result = [];
        foreach ($entities as $key => $entity) {
            $websiteId = $entity->getWebsiteId();
            $item = $this->cache[$websiteId] ?? null;
            $item && $result[$key] = $item;
        }

        return $result;
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
