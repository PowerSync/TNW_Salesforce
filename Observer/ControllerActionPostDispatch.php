<?php
namespace TNW\Salesforce\Observer;

use Magento\Framework\Event\Observer;

class ControllerActionPostDispatch implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Event\Manager
     */
    private $eventManager;

    public function __construct(
        \Magento\Framework\Event\Manager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->eventManager->dispatch('tnw_salesforce_entities_sync');
    }
}