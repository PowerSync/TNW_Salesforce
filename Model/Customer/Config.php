<?php

namespace TNW\Salesforce\Model\Customer;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem;
use Magento\Store\Api\WebsiteRepositoryInterface;
use Magento\Store\Model\StoreManagerInterface;
use TNW\Salesforce\Model\Config\WebsiteDetector;

/**
 * Configuration for customer Salesforce client
 */
class Config extends \TNW\Salesforce\Model\Config
{
    /**
     * @var array
     */
    private $configPathMap;

    /**
     * @param ScopeConfigInterface $scopeConfig
     * @param DirectoryList $directoryList
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     * @param WebsiteRepositoryInterface $websiteRepository
     * @param Http $request
     * @param Filesystem $filesystem
     * @param WebsiteDetector $websiteDetector
     * @param array $configPathMap
     */
    public function __construct(
        ScopeConfigInterface $scopeConfig,
        DirectoryList $directoryList,
        EncryptorInterface $encryptor,
        StoreManagerInterface $storeManager,
        WebsiteRepositoryInterface $websiteRepository,
        Http $request,
        Filesystem $filesystem,
        WebsiteDetector $websiteDetector,
        array $configPathMap = []
    ) {
        parent::__construct(
            $scopeConfig,
            $directoryList,
            $encryptor,
            $storeManager,
            $websiteRepository,
            $request,
            $filesystem,
            $websiteDetector
        );
        $this->configPathMap = $configPathMap;
    }

    /**
     * Get Customer Integration status
     *
     * @param int|null $websiteId
     * @return bool
     */
    public function getCustomerStatus($websiteId = null)
    {
        return (bool)$this->getStoreConfig('tnwsforce_customer/general/active', $websiteId);
    }

    /**
     * Checks if Magento can rename account names on Salesforce
     * @param null $websiteId
     * @return bool
     */
    public function canRenameAccount($websiteId = null)
    {
        return (bool)$this->getStoreConfig('tnwsforce_customer/general/account_name', $websiteId);
    }

    /**
     * Get Customer sync for all customer groups or not
     *
     * @param int|null $websiteId
     * @return bool
     */
    public function getCustomerAllGroups($websiteId = null)
    {
        return (bool)$this->getStoreConfig('tnwsforce_customer/general/sync_groups', $websiteId);
    }

    /**
     * Get Customer groups ids that allowed to sync
     *
     * @param int|null $websiteId
     * @return array
     */
    public function getCustomerSyncGroups($websiteId = null): array
    {

        $value = (string)($this->getStoreConfig('tnwsforce_customer/general/customer_group', $websiteId));

        $result = [];
        $value = trim($value);
        if ($value !== '') {
            $result = array_unique(
                array_filter(
                    array_map('intval', explode(',', $value)),
                    static function(int $customerGroupId) {
                        return $customerGroupId === 0 || !empty($customerGroupId);
                    }
                )
            );
        }

        return $result;
    }

    /**
     * Default owner for Accounts and Contacts
     * This default owner will be assigned to Contact and/or Account when created.
     *
     * @param null $websiteId
     * @return string
     */
    public function defaultOwner($websiteId = null)
    {
        return $this->getStoreConfig($this->configPathMap['tnwsforce_customer/general/default_owner'], $websiteId);
    }

    /**
     * Contact assignee
     * Use Default Owner - when a new Contact is created, the Contact will be assigned to the 'Default Owner' value set above.
     * Retain Owner from Existing Account - If a matching Account already exists in Salesforce, Magento will assign
     * a new Contact to whomever owns the Account. Otherwise Magento will fall back to 'Default Owner' value set above.
     *
     * @param null $websiteId
     * @return int
     */
    public function contactAssignee($websiteId = null)
    {
        return (int)$this->getStoreConfig('tnwsforce_customer/general/contact_assignee', $websiteId);
    }

    /**
     * Get is customer accounts shared between Websites or accounts defined per every website
     *
     * @param int|null $websiteId
     *
     * @return int 0 - Global, 1 - Per Website
     */
    public function accountShareScope($websiteId = null)
    {
        return (int)$this->getStoreConfig('customer/account_share/scope', $websiteId);
    }
}
