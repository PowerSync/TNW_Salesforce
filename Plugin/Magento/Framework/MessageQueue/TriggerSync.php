<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
namespace TNW\Salesforce\Plugin\Magento\Framework\MessageQueue;

use Magento\Framework\Event\Manager;
use TNW\Salesforce\Model\Config;

/**
 * Class ShippingInformationManagement
 */
class TriggerSync
{
    /**
     * @var Manager
     */
    private $eventManager;

    /**
     * @var Config
     */
    protected $salesforceConfig;

    /**
     * ControllerActionPostDispatch constructor.
     * @param Manager $eventManager
     * @param Config $salesforceConfig
     */
    public function __construct(
        Manager $eventManager,
        Config $salesforceConfig
    ) {
        $this->eventManager = $eventManager;
        $this->salesforceConfig = $salesforceConfig;
    }

    public function afterAcknowledge(
        $subject,
        $result,
    )
    {
        if ($this->salesforceConfig->getSalesforceStatus()) {
            $this->eventManager->dispatch('tnw_salesforce_entities_sync');
        }

        return $result;
    }

}
