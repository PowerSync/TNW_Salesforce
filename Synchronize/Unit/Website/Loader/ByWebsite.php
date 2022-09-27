<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Synchronize\Unit\Website\Loader;

use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Store;
use Magento\Store\Api\Data\WebsiteInterface;
use TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface;

/**
 * Load By Website
 */
class ByWebsite implements LoadLoaderInterface, \TNW\Salesforce\Synchronize\Unit\Load\PreLoaderInterface
{
    const LOAD_BY = 'website';

    /**
     * @var Store\Model\WebsiteFactory
     */
    private $websiteFactory;

    /**
     * @var Store\Model\ResourceModel\Website
     */
    private $resourceWebsite;

    /** @var \TNW\Salesforce\Service\Synchronize\Unit\Load\PreLoadEntities */
    private $preLoadEntities;

    /** @var Store\Model\ResourceModel\Website\CollectionFactory */
    private $collectionFactory;

    /**
     * ByWebsite constructor.
     *
     * @param Store\Model\WebsiteFactory        $websiteFactory
     * @param Store\Model\ResourceModel\Website $resourceWebsite
     */
    public function __construct(
        Store\Model\WebsiteFactory                                    $websiteFactory,
        Store\Model\ResourceModel\Website                             $resourceWebsite,
        \TNW\Salesforce\Service\Synchronize\Unit\Load\PreLoadEntities $preLoadEntities,
        \Magento\Store\Model\ResourceModel\Website\CollectionFactory  $collectionFactory
    ) {
        $this->websiteFactory = $websiteFactory;
        $this->resourceWebsite = $resourceWebsite;
        $this->preLoadEntities = $preLoadEntities;
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Load Type
     *
     * @return string
     */
    public function loadBy()
    {
        return self::LOAD_BY;
    }

    /**
     * Load
     *
     * @param int   $entityId
     * @param array $additional
     *
     * @return WebsiteInterface
     */
    public function load($entityId, array $additional)
    {
        $entity = $this->preLoadEntities->execute($this, [$entityId])[$entityId] ?? null;
        if ($entity) {
            return $entity;
        }
        $website = $this->websiteFactory->create();
        $this->resourceWebsite->load($website, $entityId);

        return $website;
    }

    /**
     * @inheritDoc
     */
    public function createCollectionInstance(): AbstractDb
    {
        return $this->collectionFactory->create();
    }
}
