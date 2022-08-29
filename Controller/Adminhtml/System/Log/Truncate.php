<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Controller\Adminhtml\System\Log;

class Truncate extends \Magento\Backend\App\Action
{
    /**
     * @var \TNW\Salesforce\Model\ResourceModel\Log
     */
    protected $resourceLogger;

    /**
     * Truncate constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \TNW\Salesforce\Model\ResourceModel\Log $resourceLogger
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \TNW\Salesforce\Model\ResourceModel\Log $resourceLogger
    ) {
        $this->resourceLogger = $resourceLogger;
        parent::__construct($context);
    }

    /**
     * Dispatch request
     *
     * @return \Magento\Framework\Controller\ResultInterface|\Magento\Framework\App\ResponseInterface
     * @throws \Magento\Framework\Exception\NotFoundException
     */
    public function execute()
    {
        try {
            $this->resourceLogger->getConnection()
                ->truncateTable($this->resourceLogger->getMainTable());
        } catch (\Exception $e) {
            $this->getMessageManager()
                ->addErrorMessage($e->getMessage(), 'backend');
        }

        return $this->resultRedirectFactory->create()
            ->setPath('tnw_salesforce/system_log/view');
    }
}
