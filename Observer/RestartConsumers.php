<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use TNW\Salesforce\Service\MessageQueue\RestartConsumers as RestartConsumersService;

/**
 *  Put poison pill and restart consumers
 */
class RestartConsumers implements ObserverInterface
{

    /**
     * @var RestartConsumersService
     */
    private $restartConsumersService;

    /**
     * @param RestartConsumers $restartConsumers
     */
    public function __construct(
        RestartConsumersService $restartConsumersService
    ) {
        $this->restartConsumersService = $restartConsumersService;
    }

    /**
     * Execute
     *
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $this->restartConsumersService->execute();
    }
}
