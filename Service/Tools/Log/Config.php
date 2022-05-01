<?php
declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Service\Tools\Log;

use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Log config service.
 */
class Config
{
    public const SALESFORCE_LOG_DIRECTORY = 'sforce';
    private const LOG_LINES_COUNT_XML = 'tnwsforce_general/debug/log_lines_count';

    /** @var ScopeConfigInterface */
    private $scopeConfig;

    /**
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(ScopeConfigInterface $scopeConfig)
    {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * @return int
     */
    public function getLinesCount(): int
    {
        return (int)$this->scopeConfig->getValue(self::LOG_LINES_COUNT_XML);
    }
}
