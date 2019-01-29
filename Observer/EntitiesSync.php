<?php
namespace TNW\Salesforce\Observer;

use Magento\Framework\Event\Observer;

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
     * @var \TNW\Salesforce\Synchronize\Entity
     */
    private $entitySynchronize;

    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    private $messageManager;

    /**
     * EntitiesSync constructor.
     * @param Entities $entities
     * @param \TNW\Salesforce\Synchronize\Queue\Entity $entitySynchronize
     */
    public function __construct(
        \TNW\Salesforce\Observer\Entities $entities,
        \TNW\Salesforce\Synchronize\Queue\Entity $entitySynchronize,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->entities = $entities;
        $this->entitySynchronize = $entitySynchronize;
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
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e);
        }

        $this->entities->clean();
    }
}
