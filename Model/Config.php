<?php
namespace TNW\Salesforce\Model;

use Magento\Framework\DataObject;
use Magento\Store\Model\ScopeInterface;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

/**
 * Class Config
 * @package TNW\Salesforce\Model
 */
class Config extends DataObject
{
    const SFORCE_BASIC_PREFIX = 'tnw_mage_basic__';
    const SFORCE_ENTERPRISE_PREFIX = 'tnw_mage_enterp__';
    const SFORCE_WEBSITE_ID = 'Magento_Website__c';
    const SFORCE_MAGENTO_ID = 'Magento_ID__c';
    const BASE_DAY = 7;

    /** @var ScopeConfigInterface  */
    protected $scopeConfig;

    /** @var DirectoryList  */
    protected $directoryList;

    /** @var EncryptorInterface  */
    protected $encryptor;

    /** @var StoreManagerInterface  */
    protected $storeManager;

    /**
     * @var array;
     */
    protected $isIntegrationActive = null;

    /** @var Http  */
    protected $request;

    /**
     * @var \Magento\Framework\Filesystem
     */
    private $filesystem;

    /**
     * @param ScopeConfigInterface  $scopeConfig
     * @param DirectoryList         $directoryList
     * @param EncryptorInterface    $encryptor
     * @param StoreManagerInterface $storeManager
     * @param Http                  $request
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        Http $request,
        \Magento\Framework\Filesystem $filesystem
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->request = $request;

        parent::__construct();
    }

    /**
     * Get magento product Id field name in Salesforce database
     * @return string
     */
    public function getMagentoIdField()
    {
        return self::SFORCE_BASIC_PREFIX . self::SFORCE_MAGENTO_ID;
    }

    /**
     * Get magento product Id field name in Salesforce database
     * @return string
     */
    public function getWebsiteIdField()
    {
        return self::SFORCE_BASIC_PREFIX . self::SFORCE_WEBSITE_ID;
    }

    /**
     * Get TNW general status
     *
     * @param int|null $websiteId
     * @return string
     */
    public function getSalesforceStatus($websiteId = null)
    {
        if (!is_null($websiteId)) {
            $value = $this->scopeConfig->getValue(
                'tnwsforce_general/salesforce/active',
                ScopeInterface::SCOPE_WEBSITE,
                $websiteId
            );
        } else {
            $value = $this->getStoreConfig(
                'tnwsforce_general/salesforce/active'
            );
        }

        return $value ? true : false;
    }

    /**
     * Get Salesfoce username from config
     *
     * @param int|null $websiteId
     * @return string
     */
    public function getSalesforceUsername($websiteId = null)
    {
        $username = $this->scopeConfig->getValue(
            'tnwsforce_general/salesforce/username',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );
        return $username;
    }

    /**
     * Get Salesfoce password from config
     *
     * @param int|null $websiteId
     * @return string
     */
    public function getSalesforcePassword($websiteId = null)
    {
        $password = $this->scopeConfig->getValue(
            'tnwsforce_general/salesforce/password',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        $decrypt = $this->encryptor->decrypt($password);
        if (!empty($decrypt)) {
            return $decrypt;
        }

        return $password;
    }

    /**
     * Get Salesfoce token from config
     *
     * @param int|null $websiteId
     * @return string
     */
    public function getSalesforceToken($websiteId = null)
    {
        $token = $this->scopeConfig->getValue(
            'tnwsforce_general/salesforce/token',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        $decrypt = $this->encryptor->decrypt($token);
        if (!empty($decrypt)) {
            return $decrypt;
        }

        return $token;
    }

    /**
     * Get Salesfoce wsdl path from config
     *
     * @param int|null $websiteId
     * @return string
     */
    public function getSalesforceWsdl($websiteId = null)
    {
        $dir = $this->scopeConfig->getValue(
            'tnwsforce_general/salesforce/wsdl',
            ScopeInterface::SCOPE_WEBSITE,
            $websiteId
        );

        if (strpos(trim($dir), '{var}') === 0) {
            $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            return $varDir->getAbsolutePath(str_replace('{var}', '', $dir));
        }

        $root = $this->directoryList->getPath(DirectoryList::ROOT);

        return $root.DIRECTORY_SEPARATOR.$dir;
    }

    /**
     * Get Log status
     *
     * @param int|null $websiteId
     * @return int
     */
    public function getLogStatus($websiteId = null)
    {
        $status = (int) $this->scopeConfig->getValue(
            'tnwsforce_general/debug/logstatus'
        );

        return $status;
    }

    /**
     * @return int
     */
    public function logBaseDay()
    {
        $baseDay = $this->scopeConfig->getValue(
            'tnwsforce_general/debug/logbaseday'
        );

        if (!is_int($baseDay) || $baseDay < 1) {
            $baseDay = self::BASE_DAY;
        }

        return $baseDay;
    }

    /**
     * Get DB Log status
     *
     * @param int|null $websiteId
     * @return int
     */
    public function getDbLogStatus($websiteId = null)
    {
        $status = (int) $this->scopeConfig->getValue(
            'tnwsforce_general/debug/dblogstatus'
        );

        return $status;
    }

    /**
     * Get Log status
     *
     * @return int
     */
    public function getLogDebug($websiteId = null)
    {
        $status = (int) $this->scopeConfig->getValue(
            'tnwsforce_general/debug/logdebug'
        );

        return $status;
    }

    /**
     * @return string
     */
    public function getDbLogLimit()
    {
        return $this->getStoreConfig('tnwsforce_general/debug/db_log_limit');
    }

    /**
     * Get log path
     * @return string
     */
    public function getLogDir()
    {
        return $this->directoryList->getPath(DirectoryList::LOG)
            . DIRECTORY_SEPARATOR . 'sforce.log';
    }

    #region Common methods to get config values
    /**
     * Get Store Id passed to request or get current if nothing
     * @return int
     */
    public function getStoreId()
    {
        $store = null;
        $storeId = $this->request->getParam('store');
        if ($storeId) {
            if ($storeId == 'undefined') {
                $storeId = 0;
            }
            if (!is_array($storeId)) {
                $store = $this->storeManager->getStore($storeId);
            }
        }
        if (!$store) {
            $store = $this->storeManager->getStore(0);
        }

        return (int)$store->getId();
    }

    /**
     * Get Website Id passed to request or get current if nothing
     * @return int
     */
    public function getWebsiteId()
    {
        $website = null;
        $websiteId = $this->request->getParam('website');
        if ($websiteId) {
            if (!is_array($websiteId)) {
                $website = $this->storeManager->getWebsite($websiteId);
            }
        }
        if (!$website) {
            $website = $this->storeManager->getWebsite(0);
        }

        return (int)$website->getId();
    }

    /**
     * @param $path
     * @return mixed|null|string
     */
    protected function getStoreConfig($path)
    {
        $storeId = $this->getStoreId();
        if ($storeId) {
            return $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_STORE,
                $this->storeManager->getStore($storeId)
            );
        }
        $websiteId = $this->getWebsiteId();
        if ($websiteId) {
            return $this->scopeConfig->getValue(
                $path,
                ScopeInterface::SCOPE_WEBSITE,
                $this->storeManager->getWebsite($websiteId)->getCode()
            );
        }

        $value = $this->scopeConfig->getValue($path);

        return $value;
    }
    #endregion

    /**
     * @return array|bool
     */
    public function isSalesForceIntegrationActive()
    {
        if ($this->isIntegrationActive === null) {

            $this->isIntegrationActive = false;

            foreach ($this->storeManager->getWebsites() as $website) {

                if ($this->getSalesforceStatus($website->getId())) {
                    $this->isIntegrationActive = true;
                }
            }
        }

        return $this->isIntegrationActive;
    }
}
