<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\ResourceModel\Log\File\Grid;

use Exception;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Data\Collection as DataCollection;
use Magento\Framework\Data\Collection\EntityFactoryInterface;
use Magento\Framework\Data\Collection\Filesystem as FilesystemCollection;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem;
use TNW\Salesforce\Model\Log\File;
use TNW\Salesforce\Model\Log\FileFactory;
use TNW\Salesforce\Service\Tools\Log\LoadFileData;

/**
 * Salesforce log file grid collection.
 */
class Collection extends FilesystemCollection
{
    private const FILE_MASK = '/^.*\.log$/i';

    /** @var string|null */
    protected $logDir;

    /** @var string */
    protected $_itemObjectClass = File::class;

    /** @var LoadFileData */
    private $loadLogFileData;

    /** @var FileFactory */
    private $fileFactory;

    /** @var DirectoryList */
    private $directoryList;

    /**
     * @param LoadFileData                $loadFileData
     * @param FileFactory                 $fileFactory
     * @param DirectoryList               $directoryList
     * @param EntityFactoryInterface|null $entityFactory
     * @param Filesystem|null             $filesystem
     *
     * @throws FileSystemException|Exception
     */
    public function __construct(
        LoadFileData $loadFileData,
        FileFactory $fileFactory,
        DirectoryList $directoryList,
        EntityFactoryInterface $entityFactory = null,
        Filesystem $filesystem = null
    ) {
        parent::__construct($entityFactory, $filesystem);

        $this->loadLogFileData = $loadFileData;
        $this->fileFactory = $fileFactory;
        $this->directoryList = $directoryList;
        $this->setCollectRecursively(false)
            ->setFilesFilter(self::FILE_MASK)
            ->addTargetDir($this->getTargetDir());
    }

    /**
     * Add order to collection.
     *
     * @param $field
     * @param $direction
     *
     * @return DataCollection
     */
    public function addOrder($field, $direction): DataCollection
    {
        return $this->setOrder($field, $direction);
    }

    /**
     * @inheritDoc
     *
     * @throws FileSystemException
     */
    protected function _generateRow($filename)
    {
        $row = parent::_generateRow($filename);
        $logFileModel = $this->fileFactory->create();
        $fileId = $this->getBasePath($filename);
        $this->loadLogFileData->execute($logFileModel, $fileId);
        $fileData = $logFileModel->getData();

        return $row + $fileData;
    }

    /**
     * @throws FileSystemException
     */
    private function getBasePath(string $absoluteFilePath): string
    {
        $absoluteDirectoryPath = $this->directoryList->getPath(DirectoryList::LOG);
        $basePath = str_replace($absoluteDirectoryPath, '', $absoluteFilePath);

        return trim($basePath, DIRECTORY_SEPARATOR);
    }

    /**
     * Get target dir.
     *
     * @throws FileSystemException
     */
    private function getTargetDir(): string
    {
        $baseLogDirectory = $this->directoryList->getPath(DirectoryList::LOG);
        if (!$this->logDir) {
            return $baseLogDirectory;
        }

        return $baseLogDirectory . DIRECTORY_SEPARATOR . trim($this->logDir, DIRECTORY_SEPARATOR);
    }
}
