<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Observer;

/**
 * Website Save Commit After
 */
class WebsiteSaveCommitAfter implements \Magento\Framework\Event\ObserverInterface
{
    /**
     * @var Entities
     */
    private $entities;

    /**
     * WebsiteSaveCommitAfter constructor.
     * @param Entities $entities
     */
    public function __construct(Entities $entities)
    {
        $this->entities = $entities;
    }

    /**
     * Execute
     *
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
