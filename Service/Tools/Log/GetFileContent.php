<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Tools\Log;

use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\Io\File;
use SplFileObject;

/**
 * Get file content service.
 */
class GetFileContent
{
    private const DEFAULT_PAGE_SIZE = 250;

    /** @var File */
    private $ioFile;

    /**
     * @param File $ioFile
     */
    public function __construct(File $ioFile)
    {
        $this->ioFile = $ioFile;
    }

    /**
     * Get file content.
     *
     * @param string   $filePath
     * @param int|null $pageSize
     * @param int      $page
     *
     * @return string
     * @throws FileSystemException
     */
    public function execute(string $filePath, int $pageSize = self::DEFAULT_PAGE_SIZE, int $page = 1): string
    {
        if (!$this->ioFile->fileExists($filePath)) {
            throw new FileSystemException(__('Log file %1 no longer exist', $filePath));
        }

        $content = '';
        $pageSize = $pageSize ?? self::DEFAULT_PAGE_SIZE;
        $firstLine = ($page - 1) * $pageSize;
        $endLine = $page * $pageSize;
        $file = new SplFileObject($filePath);
        $file->seek($firstLine);
        while ($file->key() < $endLine || !$file->eof()) {
            $content .= $file->current();

            $file->next();
        }

        return $content;
    }
}
