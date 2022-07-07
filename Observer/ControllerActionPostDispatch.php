<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Observer;

use Magento\Framework\Event\Observer;
use TNW\Salesforce\Model\Config;

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
     * @var Config
     */
    protected $salesforceConfig;

    /**
     * ControllerActionPostDispatch constructor.
     * @param \Magento\Framework\Event\Manager $eventManager
     * @param Config $salesforceConfig
     */
    public function __construct(
        \Magento\Framework\Event\Manager $eventManager,
        Config $salesforceConfig
    ) {
        $this->eventManager = $eventManager;
        $this->salesforceConfig = $salesforceConfig;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        if (!$this->salesforceConfig->getSalesforceStatus()) {
            return;
        }

        $this->eventManager->dispatch('tnw_salesforce_entities_sync');
    }
}
