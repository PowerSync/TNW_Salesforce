<?php
namespace TNW\Salesforce\Synchronize\Queue;

/**
 * Class Entity
 */
class Entity
{
    /**
     * @var string
     */
    private $entityType;

    /**
     * @var Unit[]
     */
    private $resolves;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool
     */
    private $dividerPool;

    /**
     * @var \TNW\Salesforce\Synchronize\Queue
     */
    private $synchronizeQueue;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory
     */
    private $collectionQueueFactory;

    /**
     * @var \TNW\Salesforce\Model\Config\WebsiteEmulator
     */
    private $websiteEmulator;

    /**
     * Entity constructor.
     * @param string $entityType
     * @param Unit[] $resolves
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool $dividerPool
     * @param \TNW\Salesforce\Synchronize\Queue $synchronizeQueue
     * @param \TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory $collectionQueueFactory
     * @param \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
     */
    public function __construct(
        $entityType,
        array $resolves,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \TNW\Salesforce\Synchronize\Entity\DivideEntityByWebsiteOrg\Pool $dividerPool,
        \TNW\Salesforce\Synchronize\Queue $synchronizeQueue,
        \TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory $collectionQueueFactory,
        \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
    ) {
        $this->resolves = $resolves;
        $this->entityType = $entityType;
        $this->storeManager = $storeManager;
        $this->dividerPool = $dividerPool;
        $this->synchronizeQueue = $synchronizeQueue;
        $this->collectionQueueFactory = $collectionQueueFactory;
        $this->websiteEmulator = $websiteEmulator;
    }

    /**
     * Add To Queue
     *
     * @param int[] $entityIds
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function addToQueue(array $entityIds)
    {
        $entitiesByWebsite = $this->dividerPool
            ->getDividerByGroupCode($this->entityType)
            ->process($entityIds);

        array_walk($entitiesByWebsite, [$this, 'addToQueueByWebsite']);
    }

    /**
     * Add To Queue By Website
     *
     * @param int[] $entityIds
     * @param null|bool|int|string|\Magento\Store\Api\Data\WebsiteInterface $website
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Exception
     */
    public function addToQueueByWebsite(array $entityIds, $website = null)
    {
        $website = $this->storeManager->getWebsite($website);
        foreach ($entityIds as $entityId) {
            foreach ($this->resolves as $resolve) {
                $resolve->createQueue($this->entityType, $entityId, $website->getId(), 0);
            }
        }

        $this->websiteEmulator->wrapEmulationWebsite([$this, 'realtimeSynchronize'], $website->getId());
    }

    /**
     * Realtime Synchronize
     *
     * @param int $websiteId
     * @throws \Exception
     */
    public function realtimeSynchronize($websiteId)
    {
        $collection = $this->collectionQueueFactory->create()
            ->addFilterToSyncType(0)
            ->addFilterToWebsiteId($websiteId);

        $this->synchronizeQueue->synchronize($collection, $websiteId);
    }
}
