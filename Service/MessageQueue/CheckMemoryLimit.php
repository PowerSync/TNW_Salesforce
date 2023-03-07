<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Service\MessageQueue;

use TNW\Salesforce\Model\Config;

/**
 *  Put poison pill and restart consumers
 */
class CheckMemoryLimit
{
    /**
     * @var Config
     */
    protected $salesforceConfig;

    /**
     * @param Config $salesforceConfig
     */
    public function __construct(
        Config $salesforceConfig
    ) {
        $this->salesforceConfig = $salesforceConfig;
    }

    /**
     * Put poison pill
     *
     * @return void
     */
    public function execute(): void
    {
        $memory = memory_get_usage(true);
        if ($memory > $this->salesforceConfig->getMemoryLimitByte()) {
            exit(0);
        }
    }
}
