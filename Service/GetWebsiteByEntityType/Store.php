<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Api\Service\GetWebsiteByEntityType\GetWebsiteIdByEntityIdsInterface;

/**
 *  Website by store
 */
class Store implements GetWebsiteIdByEntityIdsInterface
{
    /** @var StoreManagerInterface */
    private $storeManager;

    /**
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    )
    {
        $this->storeManager = $storeManager;
    }

    /**
     * @inheritDoc
     */
    public function execute(array $entityIds): array
    {
        $result = [];
        foreach ($entityIds as $entityId) {
            $result[$entityId] = $this->storeManager->getStore($entityId)->getWebsiteId();
        }

        return $result;
    }
}
