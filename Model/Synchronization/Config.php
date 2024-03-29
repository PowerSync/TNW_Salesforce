<?php declare(strict_types=1);
/**
 * Copyright © 2022 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */
/**
 * Copyright © 2016 TechNWeb, Inc. All rights reserved.
 * See TNW_LICENSE.txt for license details.
 */

namespace TNW\Salesforce\Model\Synchronization;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Cache\State;
use Magento\Framework\App\Cache\Type\Collection;

class Config extends \TNW\Salesforce\Model\Customer\Config
{


    /**
     * Cron queue types
     */

    const CLEAN_SYSTEM_LOGS = 8;


    /**
     * @var \Magento\Framework\Stdlib\DateTime\TimezoneInterface
     */
    protected $dateTime;

    /**
     * @var \Magento\Framework\App\ResourceConnection
     */
    protected $resource;

    /** @var WebsiteDetector  */
    protected $websiteDetector;

    /** @var Collection  */
    protected $cacheCollection;

    /** @var  State */
    protected $cacheState;

    /** @var  array */
    protected $handCache = [];

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime
     * @param \Magento\Framework\App\ResourceConnection $resource
     * @param \Magento\Framework\App\Request\Http $request
     * @param \Magento\Framework\Filesystem $filesystem
     * @param \TNW\Salesforce\Model\Config\WebsiteDetector $websiteDetector
     * @param Collection $cacheCollection
     * @param State $cacheState
     * @param array $configPathMap
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        \Magento\Framework\Stdlib\DateTime\TimezoneInterface $dateTime,
        \Magento\Framework\App\ResourceConnection $resource,
        \Magento\Framework\App\Request\Http $request,
        \Magento\Framework\Filesystem $filesystem,
        \TNW\Salesforce\Model\Config\WebsiteDetector $websiteDetector,
        Collection $cacheCollection,
        State $cacheState,
        array $configPathMap = []
    ) {
        $this->dateTime = $dateTime;
        $this->resource = $resource;
        $this->websiteDetector = $websiteDetector;
        $this->cacheCollection = $cacheCollection;
        $this->cacheState = $cacheState;
        parent::__construct(
            $scopeConfig,
            $directoryList,
            $encryptor,
            $storeManager,
            $websiteRepository,
            $request,
            $filesystem,
            $websiteDetector,
            $configPathMap
        );
    }

    /**
     * Magento Time
     *
     * @param int $websiteId
     * @return int
     */
    public function getMagentoTime($websiteId = null)
    {
        return $this->dateTime->scopeTimeStamp($websiteId);
    }

}
