<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Controller\Adminhtml\LogFile\File;

use Laminas\Http\Response;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\FileSystemException;
use Throwable;
use TNW\Salesforce\Model\Log\FileFactory;
use TNW\Salesforce\Service\Tools\Log\Config;
use TNW\Salesforce\Service\Tools\Log\GetFileContent;
use TNW\Salesforce\Service\Tools\Log\LoadFileData;

/**
 * Log file view action.
 */
class View extends Action implements HttpPostActionInterface
{
    /** @var JsonFactory */
    private $jsonFactory;

    /** @var GetFileContent */
    private $getFileContent;

    /** @var Config */
    private $config;

    /** @var LoadFileData */
    private $loadFileData;

    /** @var FileFactory */
    private $fileFactory;

    /**
     * @param JsonFactory    $jsonFactory
     * @param GetFileContent $getFileContent
     * @param Config         $config
     * @param LoadFileData   $loadFileData
     * @param FileFactory    $fileFactory
     */
    public function __construct(
        JsonFactory $jsonFactory,
        GetFileContent $getFileContent,
        Config $config,
        LoadFileData $loadFileData,
        FileFactory $fileFactory
    ) {
        $this->jsonFactory = $jsonFactory;
        $this->getFileContent = $getFileContent;
        $this->config = $config;
        $this->loadFileData = $loadFileData;
        $this->fileFactory = $fileFactory;
    }

    /**
     * @inerhitDoc
     */
    public function execute()
    {
        $request = $this->getRequest();
        $result = $this->jsonFactory->create();
        if (!$request->getParam('isAjax')) {
            $message = __('Request to must be ajax only')->render();

            return $result->setHttpResponseCode(Response::STATUS_CODE_501)->setJsonData($message);
        }

        $fileId = (string)$request->getParam('id');
        $page = (int)$request->getParam('page');

        try {
            $resultData = $this->getContent($fileId, $page);
        } catch (Throwable $exception) {
            return $result->setHttpResponseCode(Response::STATUS_CODE_500)->setJsonData($exception->getMessage());
        }

        return $result->setData($resultData);
    }

    /**
     * @param string $fileId
     * @param int    $page
     *
     * @return array
     * @throws FileSystemException
     */
    private function getContent(string $fileId, int $page): array
    {
        $linesCount = $this->config->getLinesCount();
        $file = $this->fileFactory->create();
        $this->loadFileData->execute($file, $fileId);
        $content = $this->getFileContent->execute($file->getAbsolutePath(), $linesCount, $page);

        return ['content' => $content];
    }
}
