<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Cron;

use TNW\Salesforce\Model\ResourceModel\Log as LogResource;

/**
 * Class CurrencyRatesUpdate
 *
 * @package TNW\Salesforce\Cron
 */
class ClearDbLog
{
    /** @var LogResource */
    private $logResource;

    /**
     * @var \TNW\Salesforce\Model\Config
     */
    private $salesforceConfig;

    /**
     * UpdateCurrencyRates constructor.
     *
     * @param LogResource $logResource
     */
    public function __construct(
        \TNW\Salesforce\Model\Config $salesforceConfig,
        LogResource $logResource
    )
    {
        $this->salesforceConfig = $salesforceConfig;
        $this->logResource = $logResource;
    }

    /**
     *
     */
    public function execute()
    {
        $connection = $this->logResource->clear();
    }

}
