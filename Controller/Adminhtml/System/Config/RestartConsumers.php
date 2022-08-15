<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Controller\Adminhtml\System\Config;


use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect;
use TNW\Salesforce\Service\MessageQueue\RestartConsumers as RestartConsumersService;

/**
 * Manual restart consumers
 */
class RestartConsumers extends Action
{
    /** @var RestartConsumersService */
    private $restartConsumers;

    /**
     * @param Context                 $context
     * @param RestartConsumersService $restartConsumers
     */
    public function __construct(
        Context                 $context,
        RestartConsumersService $restartConsumers
    ) {
        parent::__construct($context);
        $this->restartConsumers = $restartConsumers;
    }

    /**
     * @return Redirect
     */
    public function execute(): Redirect
    {
        $resultRedirect = $this->resultRedirectFactory->create();

        try {
            $this->restartConsumers->execute();
            $this->messageManager->addSuccessMessage(__('Consumers will be restarted soon!'));
        } catch (\Throwable $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        $resultRedirect->setPath('adminhtml/system_config/edit', ['section' => 'tnwsforce_general']);

        return $resultRedirect;
    }
}
