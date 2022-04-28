<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Controller\Adminhtml\LogFile\Sync;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\View\Result\PageFactory;
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
        $resultPage = $this->resultPageFactory->create();
        $resultPage->setActiveMenu('TNW_Salesforce::tools_sync_log');

        $fileId = $this->getRequest()->getParam('id');
        $resultPage->getConfig()->getTitle()->prepend((__('View Synchronization Log File: %1', )));

        return $resultPage;
    }

    /**
     * @throws FileSystemException
     */
    private function getFileModel(string $id): File
    {
        $file = $this->fileFactory->create();
        $this->loadFileData->execute($file, $id);


    }
}
