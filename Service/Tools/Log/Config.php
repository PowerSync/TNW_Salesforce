<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace  TNW\Salesforce\Service\Tools\Log;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Log config service.
 */
class Config
{
    private const SALESFORCE_LOG_DIRECTORY = 'sforce';

    /** @var DirectoryList */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getSalesforceLogFullPath(): string
    {
        return $this->getNativeLogFullPath() . DIRECTORY_SEPARATOR . self::SALESFORCE_LOG_DIRECTORY;
    }

    /**
     * @return string
     * @throws FileSystemException
     */
    public function getNativeLogFullPath(): string
    {
        return $this->directoryList->getPath(DirectoryList::LOG);
    }
}
