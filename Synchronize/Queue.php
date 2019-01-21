<?php
namespace TNW\Salesforce\Synchronize;

class Queue
{
    /**
     * @var Queue\Manager[]
     */
    private $managers;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    public function __construct(
        array $managers,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->managers = $managers;
        $this->storeManager = $storeManager;
    }

    /**
     * @param string $entityType
     * @param int $entityId
     * @param null $website
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addById($entityType, $entityId, $website = null)
    {
        $website = $this->storeManager->getWebsite($website);
        foreach ($this->managers as $manager) {
            if (strcasecmp($manager->entityType(), $entityType) !== 0) {
                continue;
            }

            $manager->generate($entityType, $entityId, $website->getId());
        }
    }
}
