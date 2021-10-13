<?php
declare(strict_types=1);

namespace TNW\Salesforce\Observer;

use Magento\Framework\Event\Observer;

/**
 * Controller Action Post Dispatch
 */
class ControllerActionPostDispatch implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var \Magento\Framework\Event\Manager
     */
    private $eventManager;

    /**
     * ControllerActionPostDispatch constructor.
     * @param \Magento\Framework\Event\Manager $eventManager
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->eventManager->dispatch('tnw_salesforce_entities_sync');
    }
}
