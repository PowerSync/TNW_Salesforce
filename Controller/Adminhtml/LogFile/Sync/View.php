<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Controller\Adminhtml\LogFile\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\View\Result\PageFactory;
use Throwable;
use TNW\Salesforce\Model\Log\File;
use TNW\Salesforce\Model\Log\FileFactory;
use TNW\Salesforce\Service\Tools\Log\LoadFileData;

/**
 * View log file action.
 */
class View extends Action
{
    /** @var PageFactory */
    private $resultPageFactory;

    /** @var LoadFileData */
    private $loadFileData;

    /** @var FileFactory */
    private $fileFactory;

    /**
     * @param Context      $context
     * @param PageFactory  $resultPageFactory
     * @param LoadFileData $loadFileData
     * @param FileFactory  $fileFactory
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        LoadFileData $loadFileData,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->resultPageFactory = $resultPageFactory;
        $this->loadFileData = $loadFileData;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $fileId = (string)$this->getRequest()->getParam('id');
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TNW_Salesforce::tools_sync_log');

        try {
            $file = $this->getFileModel($fileId);
            $resultPage->getConfig()->getTitle()->prepend((__('View Synchronization Log File: %1', $file->getName())));
        } catch (Throwable $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
            $resultPage = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('*/*/index');
        }

        return $resultPage;
    }

    /**
     * @param string $fileId
     *
     * @return File
     * @throws FileSystemException
     */
    private function getFileModel(string $fileId): File
    {
        $model = $this->fileFactory->create();
        $this->loadFileData->execute($model, $fileId);
        if (!$model->getId()) {
            throw new FileSystemException(__('Requested log file %1 no longer exists.', $fileId));
        }

        return $model;
    }
}
