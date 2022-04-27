<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Tools\Log;

use _PHPStan_76800bfb5\Nette\Utils\DateTime;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Filesystem\Io\File as IoFile;

/**
 * Class ExtractDataFromFile
 * @package TNW\Salesforce\Service\Tools\Log
 */
class ExtractDataFromFile
{
    /** @var IoFile */
    private $ioFile;

    /** @var File */
    private $file;

    /**
     * @param IoFile $ioFile
     * @param File   $file
     */
    public function __construct(IoFile $ioFile, File $file)
    {
        $this->ioFile = $ioFile;
        $this->file = $file;
    }

    /**
     * @throws FileSystemException
     */
    public function execute(string $path): array
    {
        $fileInfo = $this->ioFile->getPathInfo($path);
        $fileName = $fileInfo['basename'] ?? '';
        $statistics = $this->file->stat($path);
        $createdAt = $statistics['ctime'] ?? '';
        // @TODO Convert timestamp to gmt date.
//        $date = date(, $createdAt);
//        $date->setTimestamp($createdAt);
//        $createdAt = $date->getTimestamp();

        return [
            'id' => $fileName,
            'time' => (string)$createdAt,
            'name' => $fileName,
            'path' => $fileInfo['filename'] ?? '',
            'extension' => $fileInfo['extension'] ?? '',
            'display_name' => $fileName,
            'size' => $statistics['size'] ?? '',
        ];
    }
}
