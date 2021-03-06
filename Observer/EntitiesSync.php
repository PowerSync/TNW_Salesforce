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
     * @var \TNW\Salesforce\Synchronize\Queue\Add
     */
    private $entityQueue;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * EntitiesSync constructor.
     * @param \TNW\Salesforce\Observer\Entities $entities
     * @param \TNW\Salesforce\Synchronize\Queue\Add $entityQueue
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        \TNW\Salesforce\Observer\Entities $entities,
        \TNW\Salesforce\Synchronize\Queue\Add $entityQueue,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->entities = $entities;
        $this->entityQueue = $entityQueue;
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
            $this->entityQueue->addToQueue($this->entities->entityIds());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        $this->entities->clean();
    }
}
