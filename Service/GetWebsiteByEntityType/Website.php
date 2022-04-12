<?php
declare(strict_types=1);

namespace TNW\Salesforce\Service\GetWebsiteByEntityType;

use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Api\Service\GetWebsiteByEntityType\GetWebsiteIdByEntityIdsInterface;

/**
 *  Website by website ids
 */
class Website implements GetWebsiteIdByEntityIdsInterface
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
            $website = $this->storeManager->getWebsite($entityId);
            $websiteId = $website->getId();
            if ($websiteId) {
                $result[$entityId] = $websiteId;
            }
        }

        return $result;
    }
}
