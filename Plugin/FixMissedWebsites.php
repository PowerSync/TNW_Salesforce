<?php
declare(strict_types=1);

namespace TNW\Salesforce\Plugin;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg;

class FixMissedWebsites
{

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /**
     * FixMissedWebsites constructor.
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
    }

    /**
     * @param $website
     * @return mixed
     */
    public function getDefaultStoreId($website)
    {
        $defaultGroupId = $website->getDefaultGroupId();
        $defaultGroup = $this->storeManager->getGroup($defaultGroupId);
        return $defaultGroup->getDefaultStoreId();
    }

    /**
     * @param DivideEntityByWebsiteOrg $subject
     * @param $result
     * @param $entity
     * @return array|mixed
     * @throws LocalizedException
     */
    public function afterGetEntityWebsiteIds(
        DivideEntityByWebsiteOrg $subject,
        $result,
        $entity
    ) {
        if (in_array(0, $result)) {
            $result = array_filter($result);
            if (empty($result)) {
                $defaultWebsite = $this->storeManager->getWebsite(true);
                $result[] = $defaultWebsite->getId();
                $entity->setWebsiteId($defaultWebsite->getId());
                $entity->setStoreId($this->getDefaultStoreId($defaultWebsite));
            }
        }

        return $result;
    }
}
