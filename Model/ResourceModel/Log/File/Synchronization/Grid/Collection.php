<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\ResourceModel\Log\File\Synchronization\Grid;

use Exception;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Data\Collection\Filesystem as FilesystemCollection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use TNW\Salesforce\Service\Tools\Log\Config;
use TNW\Salesforce\Service\Tools\Log\ExtractDataFromFile;

/**
 * Salesforce synchronization log grid collection.
 */
class Collection extends FilesystemCollection
{
    private const FILE_MASK = '/^.*\.log$/i';

    /** @var ExtractDataFromFile */
    private $extractDataFromFile;

    /**
     * @param Config                      $logConfig
     * @param ExtractDataFromFile         $extractDataFromFile
     * @param EntityFactoryInterface|null $_entityFactory
     * @param Filesystem|null             $filesystem
     *
     * @throws FileSystemException|Exception
     */
    public function __construct(
        Config $logConfig,
        ExtractDataFromFile $extractDataFromFile,
        EntityFactoryInterface $_entityFactory = null,
        Filesystem $filesystem = null
    ) {
        parent::__construct($_entityFactory, $filesystem);

        $this->setCollectRecursively(false)
            ->setFilesFilter(self::FILE_MASK)
            ->addTargetDir($logConfig->getSalesforceLogFullPath());
        $this->extractDataFromFile = $extractDataFromFile;
    }

    /**
     * Add order to collection.
     *
     * @param $field
     * @param $direction
     *
     * @return DataCollection
     */
    public function addOrder($field, $direction)
    {
        return $this->setOrder($field, $direction);
    }

    /**
     * @inheritDoc
     * @throws FileSystemException
     */
    protected function _generateRow($filename)
    {
        $row = parent::_generateRow($filename);
        $fileData = $this->extractDataFromFile->execute($filename);

        return $row + $fileData;
    }
}
