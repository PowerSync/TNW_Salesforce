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

    /** @var \Magento\Store\Api\WebsiteRepositoryInterface  */
    protected $websiteRepository;

    /** @var array  */
    protected $websitesGrouppedByOrg = [];

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

    /** @var Config\WebsiteDetector  */
    private $websiteDetector;

    /** @var array  */
    private $credentialsConfigPaths = [
        'tnwsforce_general/salesforce/username',
        'tnwsforce_general/salesforce/password',
        'tnwsforce_general/salesforce/token',
        'tnwsforce_general/salesforce/wsdl'
    ];

    /**
     * Config constructor.
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository
     * @param Http $request
     * @param \Magento\Framework\Filesystem $filesystem
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        \Magento\Store\Api\WebsiteRepositoryInterface $websiteRepository,
        Http $request,
        \Magento\Framework\Filesystem $filesystem,
        \TNW\Salesforce\Model\Config\WebsiteDetector $websiteDetector
    ) {
        $this->scopeConfig = $scopeConfig;
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
        $this->websiteRepository = $websiteRepository;
        $this->request = $request;
        $this->websiteDetector = $websiteDetector;

        parent::__construct();
    }

    /**
     * Get magento product Id field name in Salesforce database
     * @return string
     */
    public static function getMagentoIdField()
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
        return (bool)$this->getStoreConfig('tnwsforce_general/salesforce/active', $websiteId);
    }

    /**
     * Get Salesfoce username from config
     *
     * @param int|null $websiteId
     *
     * @return string
     */
    public function getSalesforceUsername($websiteId = null)
    {
        return $this->getStoreConfig('tnwsforce_general/salesforce/username', $websiteId);
    }

    /**
     * Get Salesfoce password from config
     *
     * @param int|null $websiteId
     * @return string
     */
    public function getSalesforcePassword($websiteId = null)
    {
        $password = $this->getStoreConfig('tnwsforce_general/salesforce/password', $websiteId);

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
        $token = $this->getStoreConfig('tnwsforce_general/salesforce/token', $websiteId);

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
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getSalesforceWsdl($websiteId = null)
    {
        $dir = $this->getStoreConfig('tnwsforce_general/salesforce/wsdl', $websiteId);

        if (strpos(trim($dir), '{var}') === 0) {
            $varDir = $this->filesystem->getDirectoryRead(DirectoryList::VAR_DIR);
            return $varDir->getAbsolutePath(str_replace('{var}', '', $dir));
        }

        $root = $this->directoryList->getPath(DirectoryList::ROOT);

        return $root.DIRECTORY_SEPARATOR.$dir;
    }

    /**
     * @return bool
     */
    public function isDefaultOrg()
    {
        foreach ($this->credentialsConfigPaths as $configPath) {

            if ($this->getStoreConfig($configPath) != $this->scopeConfig->getValue($configPath)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array
     */
    public function getWebsitesGrouppedByOrg()
    {
        if (empty($this->websitesGrouppedByOrg)) {
            $websites = $this->websiteRepository->getList();
            foreach ($websites as $website) {
                foreach ($websites as $websiteToCompare) {

                    $isSame = true;
                    foreach ($this->credentialsConfigPaths as $configPath) {
                        if ($this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_WEBSITE, $websiteToCompare->getId()) != $this->scopeConfig->getValue($configPath, ScopeInterface::SCOPE_WEBSITE, $website->getId())) {
                            $isSame = false;
                        }
                    }

                    /**
                     * first website with the same credentials was found
                     */
                    if ($isSame) {
                        $this->websitesGrouppedByOrg[$website->getId()] = $websiteToCompare->getId();
                        break;
                    }
                }
            }
        }

        return $this->websitesGrouppedByOrg;
    }

    /**
     * @param int $websiteId
     *
     * @return mixed
     */
    public function uniqueWebsiteIdLogin($websiteId)
    {
        return $this->getWebsitesGrouppedByOrg()[$websiteId];
    }

    /**
     * Get Log status
     *
     * @param int|null $websiteId
     * @return int
     */
    public function getLogStatus($websiteId = null)
    {
        return (int)$this->getStoreConfig('tnwsforce_general/debug/logstatus', $websiteId);
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
        return (int)$this->getStoreConfig('tnwsforce_general/debug/dblogstatus', $websiteId);
    }

    /**
     * Get Log status
     *
     * @param int|null $websiteId
     *
     * @return int
     */
    public function getLogDebug($websiteId = null)
    {
        return (int)$this->getStoreConfig('tnwsforce_general/debug/logdebug', $websiteId);
    }

    /**
     * @param int|null $websiteId
     *
     * @return string
     */
    public function getDbLogLimit($websiteId = null)
    {
        return $this->getStoreConfig('tnwsforce_general/debug/db_log_limit', $websiteId);
    }

    /**
     * Get log path
     *
     * @return string
     * @throws \Magento\Framework\Exception\FileSystemException
     */
    public function getLogDir()
    {
        return $this->directoryList->getPath(DirectoryList::LOG)
            . DIRECTORY_SEPARATOR . 'sforce.log';
    }

    /**
     * @param $path
     * @param int|null $websiteId
     *
     * @return mixed|null|string
     */
    protected function getStoreConfig($path, $websiteId = null)
    {
        $scopeType = ScopeConfigInterface::SCOPE_TYPE_DEFAULT;
        $scopeCode = null;

        try {
            $websiteId = $this->websiteDetector->detectCurrentWebsite($websiteId);
        } catch (\Magento\Framework\Exception\LocalizedException $e) {
            $websiteId = null;
        }

        if ($websiteId !== null) {
            $scopeType = ScopeInterface::SCOPE_WEBSITE;
            $scopeCode = $websiteId;
        }

        return $this->scopeConfig->getValue($path, $scopeType, $scopeCode);
    }

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
