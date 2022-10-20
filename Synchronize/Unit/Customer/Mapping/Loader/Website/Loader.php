<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Synchronize\Unit\Customer\Mapping\Loader\Website;

use Magento\Store\Model\WebsiteFactory;
use TNW\Salesforce\Service\Synchronize\Unit\Load\SubEntities\Load\LoaderInterface;
use TNW\SForceEnterprise\Service\Synchronize\Unit\Load\GetWebsitesByWebsiteIds;

class Loader implements LoaderInterface
{
    /** @var WebsiteFactory */
    private $factory;

    /** @var GetWebsitesByWebsiteIds */
    private $getWebsitesByWebsiteIds;

    /**
     * @param WebsiteFactory          $factory
     * @param GetWebsitesByWebsiteIds $getWebsitesByWebsiteIds
     */
    public function __construct(
        WebsiteFactory $factory,
        GetWebsitesByWebsiteIds $getWebsitesByWebsiteIds
    ) {
        $this->factory = $factory;
        $this->getWebsitesByWebsiteIds = $getWebsitesByWebsiteIds;
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

        $websites = $this->getWebsitesByWebsiteIds->execute($websiteIds);

        $result = [];
        foreach ($entities as $key => $entity) {
            $websiteId = $entity->getWebsiteId();
            $item = $websites[$websiteId] ?? $this->factory->create();
            $item && $result[$key] = $item;
        }

        return $result;
    }
}
