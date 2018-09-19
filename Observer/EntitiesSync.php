<?php
namespace TNW\Salesforce\Observer;

use Magento\Framework\Event\Observer;

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

    public function __construct(
        \TNW\Salesforce\Observer\Entities $entities,
        \TNW\Salesforce\Synchronize\Entity $entitySynchronize
    ) {
        $this->entities = $entities;
        $this->entitySynchronize = $entitySynchronize;
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if ($this->entities->isEmpty()) {
            return;
        }

        $this->entitySynchronize->synchronize($this->entities->entityIds());
        $this->entities->clean();
    }
}