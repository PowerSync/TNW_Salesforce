<?php

namespace TNW\Salesforce\Plugin;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\File\Uploader;
use Magento\Framework\Filesystem;
use Magento\MediaGalleryApi\Api\IsPathExcludedInterface;
use Magento\MediaGallerySynchronizationApi\Api\SynchronizeFilesInterface;
use Magento\MediaGalleryUiApi\Api\ConfigInterface;
use Psr\Log\LoggerInterface;

class SaveImageInformation extends \Magento\MediaGalleryIntegration\Plugin\SaveImageInformation
{

    /**
     * @var IsPathExcludedInterface
     */
    private $isPathExcluded;

    /**
     * @var ConfigInterface
     */
    private $config;

    /**
     * @var LoggerInterface
     */
    private $log;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var SynchronizeFilesInterface
     */
    private $synchronizeFiles;

    /**
     * @var string[]
     */
    private $imageExtensions;

    /**
     * @param Filesystem $filesystem
     * @param LoggerInterface $log
     * @param IsPathExcludedInterface $isPathExcluded
     * @param SynchronizeFilesInterface $synchronizeFiles
     * @param ConfigInterface $config
     * @param array $imageExtensions
     */
    public function __construct(
        Filesystem $filesystem,
        LoggerInterface $log,
        IsPathExcludedInterface $isPathExcluded,
        SynchronizeFilesInterface $synchronizeFiles,
        ConfigInterface $config,
        array $imageExtensions
    ) {
        $this->log = $log;
        $this->isPathExcluded = $isPathExcluded;
        $this->filesystem = $filesystem;
        $this->synchronizeFiles = $synchronizeFiles;
        $this->config = $config;
        $this->imageExtensions = $imageExtensions;
    }

    /**
     * Saves asset to media gallery after save image.
     *
     * @param Uploader $subject
     * @param array $result
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @return array
     */
    public function afterSave(Uploader $subject, array $result): array
    {
        $mediaFolder = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();

        if (!$this->config->isEnabled() || substr($result['path'], 0, strlen($mediaFolder)) !== $mediaFolder) {
            return $result;
        }

        return parent::afterSave($subject, $result);
    }
}
