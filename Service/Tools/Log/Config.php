<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Tools\Log;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\App\Filesystem\DirectoryList;

/**
 * Log config service.
 */
class Config
{
    private const LOG_LINES_COUNT_XML = 'tnwsforce_general/debug/log_lines_count';

    private const SALESFORCE_LOG_DIRECTORY = 'sforce';

    /** @var DirectoryList */
    private $directoryList;

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @param DirectoryList        $directoryList
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(DirectoryList $directoryList, ScopeConfigInterface $scopeConfig)
    {
        $this->directoryList = $directoryList;
        $this->scopeConfig = $scopeConfig;
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

    /**
     * @return int
     */
    public function getLinesCount(): int
    {
        return (int)$this->scopeConfig->getValue(self::LOG_LINES_COUNT_XML);
    }
}
