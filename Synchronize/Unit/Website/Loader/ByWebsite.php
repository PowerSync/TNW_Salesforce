<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Synchronize\Unit\Website\Loader;

use Magento\Store;

/**
 * Load By Website
 */
class ByWebsite implements \TNW\Salesforce\Synchronize\Unit\LoadLoaderInterface
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

    /**
     * ByWebsite constructor.
     * @param Store\Model\WebsiteFactory $websiteFactory
     * @param Store\Model\ResourceModel\Website $resourceWebsite
     */
    public function __construct(
        Store\Model\WebsiteFactory $websiteFactory,
        Store\Model\ResourceModel\Website $resourceWebsite
    ) {
        $this->websiteFactory = $websiteFactory;
        $this->resourceWebsite = $resourceWebsite;
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
     * @param int $entityId
     * @param array $additional
     * @return \Magento\Store\Api\Data\WebsiteInterface
     */
    public function load($entityId, array $additional)
    {
        $website = $this->websiteFactory->create();
        $this->resourceWebsite->load($website, $entityId);

        return $website;
    }
}
