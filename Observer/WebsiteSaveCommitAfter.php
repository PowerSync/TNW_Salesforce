<?php
namespace TNW\Salesforce\Observer;

class WebsiteSaveCommitAfter implements \Magento\Framework\Event\ObserverInterface
{

    /**
     * @var Entities
     */
    private $entities;

    public function __construct(
        \TNW\Salesforce\Observer\Entities $entities
    ) {
        $this->entities = $entities;
    }

    /**
     * @param \Magento\Framework\Event\Observer $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /** @var \Magento\Store\Model\Website $website */
        $website = $observer->getEvent()->getData('data_object');
        $this->entities->addEntity($website);
    }
}