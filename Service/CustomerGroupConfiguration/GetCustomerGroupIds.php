<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\CustomerGroupConfiguration;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Customer\Config;

/**
 *  Customer group id configurations
 */
class GetCustomerGroupIds
{
    /** @var Config */
    private $customerConfig;

    /** @var StoreManagerInterface */
    private $storeManager;

    public function __construct(
        Config                $customerConfig,
        StoreManagerInterface $storeManager
    ) {
        $this->customerConfig = $customerConfig;
        $this->storeManager = $storeManager;
    }

    /**
     * Get active customer group ids or null if all active
     *
     * @return array|null
     * @throws NoSuchEntityException
     */
    public function execute(): ?array
    {
        $websiteId = $this->storeManager->getStore()->getWebsiteId();
        $useAllGroups = $this->customerConfig->getCustomerAllGroups($websiteId);
        $customerSyncGroupsIds = null;
        if (!$useAllGroups) {
            $customerSyncGroupsIds = $this->customerConfig->getCustomerSyncGroups($websiteId);
        }

        return $customerSyncGroupsIds;
    }
}
