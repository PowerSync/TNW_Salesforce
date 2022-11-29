<?php
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

declare(strict_types=1);

namespace TNW\Salesforce\Cron;

use Magento\Framework\App\Cache\Type\Config as ConfigCache;
use Magento\Framework\App\Config\ScopeConfigInterface;
use TNW\Salesforce\Model\Config;
use TNW\Salesforce\Service\Model\Grid\GetGridUpdatersByEntityTypes;
use Magento\Framework\App\Config\Storage\WriterInterface;

class RefreshGrids
{
    /** @var Config */
    private $config;

    /** @var GetGridUpdatersByEntityTypes */
    private $getGridUpdatersByEntityTypes;

    /** @var ScopeConfigInterface */
    private $scopeConfigWriter;

    /** @var ConfigCache */
    private $configCache;

    /**
     * @param Config                       $config
     * @param GetGridUpdatersByEntityTypes $getGridUpdatersByEntityTypes
     * @param WriterInterface              $scopeConfigWriter
     * @param ConfigCache                  $configCache
     */
    public function __construct(
        Config                       $config,
        GetGridUpdatersByEntityTypes $getGridUpdatersByEntityTypes,
        WriterInterface              $scopeConfigWriter,
        ConfigCache                  $configCache
    ) {
        $this->config = $config;
        $this->getGridUpdatersByEntityTypes = $getGridUpdatersByEntityTypes;
        $this->scopeConfigWriter = $scopeConfigWriter;
        $this->configCache = $configCache;
    }

    /**
     * @return void
     */
    public function execute(): void
    {
        if (!$this->config->getSalesforceStatus()) {
            return;
        }

        if (!$this->config->needRefreshGrids()) {
            return;
        }

        foreach ($this->getGridUpdatersByEntityTypes->execute() as $updaters) {
            foreach ($updaters as $updater) {
                $updater->execute();
            }
        }

        $this->scopeConfigWriter->save(Config::SYNCHRONIZATION_NEED_REFRESH_GRIDS, 0);
        $this->configCache->clean();
    }
}
