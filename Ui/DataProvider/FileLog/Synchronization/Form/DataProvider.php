<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Ui\DataProvider\FileLog\Synchronization\Form;

use Magento\Framework\UrlInterface;
use Magento\Ui\DataProvider\AbstractDataProvider;
use Psr\Log\LoggerInterface;
use Throwable;
use TNW\Salesforce\Model\Log\File;
use TNW\Salesforce\Model\ResourceModel\Log\File\Synchronization\Grid\CollectionFactory;
use TNW\Salesforce\Service\Tools\Log\Config;
use TNW\Salesforce\Service\Tools\Log\GetFileContent;

/**
 * Synchronization file log form data provider.
 */
class DataProvider extends AbstractDataProvider
{
    /** @var array */
    private $loadedData;

    /** @var GetFileContent */
    private $getFileContent;

    /** @var Config */
    private $logConfig;

    /** @var LoggerInterface */
    private $logger;

    /** @var UrlInterface */
    private $urlBuilder;

    /**
     * @param string            $name
     * @param string            $primaryFieldName
     * @param string            $requestFieldName
     * @param CollectionFactory $collectionFactory
     * @param GetFileContent    $getFileContent
     * @param Config            $logConfig
     * @param LoggerInterface   $logger
     * @param UrlInterface      $urlBuilder
     * @param array             $meta
     * @param array             $data
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        GetFileContent $getFileContent,
        Config $logConfig,
        LoggerInterface $logger,
        UrlInterface $urlBuilder,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->getFileContent = $getFileContent;
        $this->logConfig = $logConfig;
        $this->logger = $logger;
        $this->urlBuilder = $urlBuilder;
    }

    /**
     * @inerhitDoc
     */
    public function getData()
    {
        if (isset($this->loadedData)) {
            return $this->loadedData;
        }

        $items = $this->collection->getItems();
        /** @var File $file */
        foreach ($items as $file) {
            $this->loadedData[$file->getId()] = $this->getFormData($file);
        }

        return $this->loadedData;
    }

    /**
     * Retrieve data for form.
     *
     * @param File $file
     *
     * @return array
     */
    private function getFormData(File $file): array
    {
        $linesCount = $this->logConfig->getLinesCount();
        $href = $this->urlBuilder->getUrl("*/*/download", ['id' => $file->getId()]);
        try {
            $content = $this->getFileContent->execute($file->getAbsolutePath(), $linesCount);
        } catch (Throwable $exception) {
            $content = '';
            $this->logger->critical($exception->getMessage());
        }

        return [
            'name' => $file->getName(),
            'url' => $href,
            'content' => $content,
            'current_page' => 1,
        ];
    }
}
