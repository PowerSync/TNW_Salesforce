<?php


namespace TNW\Salesforce\Plugin\Synchronize\Queue\Website;


use Magento\Store\Api\Data\GroupInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Synchronize\Queue\CreateInterface;
use TNW\Salesforce\Synchronize\Queue\Website\CreateByBase;

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
     * @return int
     */
    public function getDefaultStoreId($website)
    {

        $defaultGroupId = $website->getDefaultGroupId();
        /** @var GroupInterface $defaultGroup */
        $defaultGroup = $this->storeManager->getGroup($defaultGroupId);
        return $defaultGroup->getDefaultStoreId();
    }

    /**
     * @param CreateInterface $subject
     * @param $result
     * @param int[] $entityIds
     * @param array $additional
     * @param callable $create
     * @param int $websiteId
     */
    public function afterProcess(
        CreateInterface $subject,
        $result,
        array $entityIds,
        array $additional,
        callable $create,
        $websiteId
    ) {

        foreach ($result as $k => $item) {
            if (empty($item->getEntityId())) {
                $defaultWebsite = $this->storeManager->getWebsite(true);

                $baseEntityIds = $item->getData('_base_entity_id');
                $baseEntityId = reset($baseEntityIds);
                $result[] = $create(
                    'website',
                    $defaultWebsite->getId(),
                    $baseEntityId,
                    ['website' => $defaultWebsite->getId()]
                );

                unset($result[$k]);
            }
        }

        return $result;
    }
}
