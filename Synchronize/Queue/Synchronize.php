<?php
namespace TNW\Salesforce\Synchronize\Queue;

/**
 * Entity Synchronize
 */
class Synchronize
{
    /**
     * @var int
     */
    private $type;

    /**
     * @var \TNW\Salesforce\Synchronize\Queue
     */
    private $synchronizeQueue;

    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory
     */
    private $collectionQueueFactory;

    /**
     * @var \Magento\Store\Api\WebsiteRepositoryInterface
     */
    private $websiteRepository;

    /**
     * @var \TNW\Salesforce\Model\Config\WebsiteEmulator
     */
    private $websiteEmulator;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * Queue constructor.
     * @param int $type
     * @param \TNW\Salesforce\Synchronize\Queue $synchronizeQueue
     * @param \TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory $collectionQueueFactory
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        $type,
        \TNW\Salesforce\Synchronize\Queue $synchronizeQueue,
        \TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory $collectionQueueFactory,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \TNW\Salesforce\Model\Config\WebsiteEmulator $websiteEmulator,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->type = $type;
        $this->synchronizeQueue = $synchronizeQueue;
        $this->collectionQueueFactory = $collectionQueueFactory;
        $this->websiteRepository = $websiteRepository;
        $this->websiteEmulator = $websiteEmulator;
        $this->messageManager = $messageManager;
    }

    /**
     * Sync Type
     *
     * @return int
     */
    public function type()
    {
        return $this->type;
    }

    /**
     * Synchronize
     *
     * @throws \Exception
     */
    public function synchronize()
    {
        foreach ($this->websiteRepository->getList() as $website) {
            $this->websiteEmulator->wrapEmulationWebsite([$this, 'synchronizeToWebsite'], $website->getId());
        }
    }

    /**
     * Synchronize To Website
     *
     * @param int $websiteId
     * @throws \Exception
     */
    public function synchronizeToWebsite($websiteId)
    {
        $collection = $this->collectionQueueFactory->create()
            ->addFilterToSyncType($this->type);

        try {
            $this->synchronizeQueue->synchronize($collection, $websiteId);
        } finally {
            if ($collection->count() > 0 && !in_array(false, $collection->walk('isSuccess'), true)) {
                $this->messageManager->addSuccessMessage('All records were synchronized successfully.');
            }

            if ($this->type === \TNW\Salesforce\Model\Config::DIRECT_SYNC_TYPE_REALTIME) {
                foreach ($collection as $queue) {
                    $collection->getResource()->delete($queue);
                }
            }
        }
    }
}
