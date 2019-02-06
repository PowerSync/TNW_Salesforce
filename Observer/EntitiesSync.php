<?php
namespace TNW\Salesforce\Observer;

use Magento\Framework\Event\Observer;
use TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory;

/**
 * Class EntitiesSync
 */
class EntitiesSync implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \TNW\Salesforce\Observer\Entities
     */
    private $entities;

    /**
     * @var \TNW\Salesforce\Synchronize\Queue
     */
    private $synchronizeQueue;

    /**
     * @var \TNW\Salesforce\Synchronize\Queue\Entity
     */
    private $entitySynchronize;

    /**
     * @var CollectionFactory
     */
    private $collectionQueueFactory;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * EntitiesSync constructor.
     * @param Entities $entities
     * @param \TNW\Salesforce\Synchronize\Queue $synchronizeQueue
     * @param \TNW\Salesforce\Synchronize\Queue\Entity $entitySynchronize
     * @param CollectionFactory $collectionQueueFactory
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \TNW\Salesforce\Observer\Entities $entities,
        \TNW\Salesforce\Synchronize\Queue $synchronizeQueue,
        \TNW\Salesforce\Synchronize\Queue\Entity $entitySynchronize,
        \TNW\Salesforce\Model\ResourceModel\Queue\CollectionFactory $collectionQueueFactory,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->entities = $entities;
        $this->synchronizeQueue = $synchronizeQueue;
        $this->entitySynchronize = $entitySynchronize;
        $this->collectionQueueFactory = $collectionQueueFactory;
        $this->messageManager = $messageManager;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->entities->isEmpty()) {
            return;
        }

        try {
            $this->entitySynchronize->addToQueue($this->entities->entityIds());

            $collection = $this->collectionQueueFactory->create()
                ->addFilterToSyncType(0);

            $this->synchronizeQueue->synchronize($collection);
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        $this->entities->clean();
    }
}
