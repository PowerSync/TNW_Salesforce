<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Controller\Adminhtml\LogFile;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory as FileResponseFactory;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\FileSystemException;
use Throwable;
use TNW\Salesforce\Model\Log\File;
use TNW\Salesforce\Model\Log\FileFactory;
use TNW\Salesforce\Service\Tools\Log\LoadFileData;

/**
 * Base download log file action.
 */
abstract class AbstractDownload extends Action
{
    /** @var string */
    protected $redirectRoutePath = '*/*/index';

    /** @var LoadFileData */
    private $loadFileData;

    /** @var FileResponseFactory */
    private $fileResponseFactory;

    /** @var FileFactory */
    private $fileFactory;

    /**
     * @param Context             $context
     * @param FileResponseFactory $fileResponseFactory
     * @param LoadFileData        $loadFileData
     * @param FileFactory         $fileFactory
     */
    public function __construct(
        Context $context,
        FileResponseFactory $fileResponseFactory,
        LoadFileData $loadFileData,
        FileFactory $fileFactory
    ) {
        parent::__construct($context);
        $this->loadFileData = $loadFileData;
        $this->fileResponseFactory = $fileResponseFactory;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $request = $this->getRequest();
        $fileId = $request->getParam('id');

        try {
            $model = $this->getFileModel($fileId);

            return $this->fileResponseFactory->create(
                $model->getName(),
                [
                    'type' => 'filename',
                    'value' => $model->getPath(),
                ],
                DirectoryList::LOG
            );
        } catch (Throwable $exception) {
            $this->messageManager->addErrorMessage($exception->getMessage());
        }

        return $this->getRedirect($this->redirectRoutePath);
    }

    /**
     * @param string $path
     *
     * @return ResultInterface
     */
    private function getRedirect(string $path): ResultInterface
    {
        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath($path);
    }

    /**
     * @throws FileSystemException
     */
    private function getFileModel($fileId): File
    {
        $model = $this->fileFactory->create();
        $this->loadFileData->execute($model, $fileId);
        if (!$model->getId()) {
            throw new FileSystemException(__('Requested log file %1 no longer exists.', $fileId));
        }

        return $model;
    }
}
