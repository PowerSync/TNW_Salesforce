<?php
namespace TNW\Salesforce\Synchronize\Queue;

class Entity
{
    /**
     * @var string
     */
    private $entityType;

    /**
     * @var Resolve[]
     */
    private $resolves;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * Entity constructor.
     * @param string $entityType
     * @param Resolve[] $resolves
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     */
    public function __construct(
        $entityType,
        array $resolves,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->resolves = $resolves;
        $this->entityType = $entityType;
        $this->storeManager = $storeManager;
    }

    /**
     * @param $entityId
     * @param $website
     * @return \TNW\Salesforce\Model\Queue[]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addToQueue($entityId, $website = null)
    {
        $website = $this->storeManager->getWebsite($website);

        $queues = [];
        foreach ($this->resolves as $resolve) {
            $queues += $resolve->generate($this->entityType, $entityId, $website->getId());
        }

        return $queues;
    }
}
